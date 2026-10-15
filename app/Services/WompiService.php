<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WompiService
{
    protected string $publicKey;
    protected string $privateKey;
    protected string $integritySecret;
    protected string $eventsSecret;
    protected string $baseUrl;
    protected string $currency;

    public function __construct()
    {
        $this->publicKey = config('wompi.public_key', '');
        $this->privateKey = config('wompi.private_key', '');
        $this->integritySecret = config('wompi.integrity_secret', '');
        $this->eventsSecret = config('wompi.events_secret', '');
        $this->baseUrl = config('wompi.base_url', 'https://sandbox.wompi.co/v1');
        $this->currency = config('wompi.currency', 'COP');
    }

    /**
     * Generate unique payment reference for subscription.
     */
    public function generateReference(Tenant $tenant, Plan $plan): string
    {
        return 'LIC-SUB-' . $tenant->id . '-' . $plan->id . '-' . time();
    }

    /**
     * Calculate Wompi Integrity Signature:
     * SHA256(reference + amountInCents + currency + integritySecret)
     */
    public function calculateIntegritySignature(string $reference, int $amountInCents, ?string $currency = null): string
    {
        $currency = $currency ?? $this->currency;
        $chain = $reference . $amountInCents . $currency . $this->integritySecret;
        return hash('sha256', $chain);
    }

    /**
     * Calculate Wompi fee surcharge:
     * Fee = (Total * pct + fixed) * (1 + vat)
     * For net amount received = baseAmount:
     * Total = (baseAmount + (fixed * (1 + vat))) / (1 - (pct * (1 + vat)))
     */
    public function calculateAmounts(float $baseAmount): array
    {
        $applySurcharge = config('wompi.apply_surcharge', true);

        if (!$applySurcharge) {
            return [
                'base_amount' => $baseAmount,
                'surcharge' => 0.0,
                'total_amount' => $baseAmount,
                'amount_in_cents' => (int) round($baseAmount * 100),
            ];
        }

        $pct = config('wompi.fee_percentage', 2.65) / 100.0;
        $fixed = config('wompi.fee_fixed', 700);
        $vat = config('wompi.fee_vat', 0.19);

        $effectivePct = $pct * (1 + $vat);
        $effectiveFixed = $fixed * (1 + $vat);

        $totalAmount = ($baseAmount + $effectiveFixed) / (1 - $effectivePct);
        $totalAmount = round($totalAmount);
        $surcharge = $totalAmount - $baseAmount;

        return [
            'base_amount' => $baseAmount,
            'surcharge' => $surcharge,
            'total_amount' => $totalAmount,
            'amount_in_cents' => (int) ($totalAmount * 100),
        ];
    }

    /**
     * Build checkout payload for Wompi Widget.
     */
    public function getCheckoutData(Tenant $tenant, Plan $plan): array
    {
        $amounts = $this->calculateAmounts((float) $plan->price_monthly);
        $amountInCents = $amounts['amount_in_cents'];
        $reference = $this->generateReference($tenant, $plan);
        $signature = $this->calculateIntegritySignature($reference, $amountInCents);

        // Record pending payment
        SubscriptionPayment::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'reference' => $reference,
            'amount_in_cents' => $amountInCents,
            'currency' => $this->currency,
            'status' => 'PENDING',
        ]);

        return [
            'public_key' => $this->publicKey,
            'currency' => $this->currency,
            'amount_in_cents' => $amountInCents,
            'base_amount' => $amounts['base_amount'],
            'surcharge' => $amounts['surcharge'],
            'total_amount' => $amounts['total_amount'],
            'formatted_base' => '$' . number_format($amounts['base_amount'], 0, ',', '.') . ' COP',
            'formatted_surcharge' => '$' . number_format($amounts['surcharge'], 0, ',', '.') . ' COP',
            'formatted_total' => '$' . number_format($amounts['total_amount'], 0, ',', '.') . ' COP',
            'reference' => $reference,
            'signature' => $signature,
            'redirect_url' => route('billing.wompi-callback'),
            'customer_email' => auth()->user()?->email ?? $tenant->email ?? ($tenant->users()->first()?->email ?? ''),
            'customer_name' => auth()->user()?->name ?? $tenant->name,
            'plan_name' => $plan->name,
            'formatted_amount' => '$' . number_format($amounts['total_amount'], 0, ',', '.') . ' COP',
        ];
    }

    /**
     * Verify Wompi Webhook event checksum.
     */
    public function verifyEventChecksum(array $eventPayload, ?string $receivedChecksum): bool
    {
        if (empty($receivedChecksum)) {
            return false;
        }

        try {
            $transaction = $eventPayload['data']['transaction'] ?? null;
            $timestamp = $eventPayload['timestamp'] ?? '';

            if (!$transaction) {
                return false;
            }

            // Formula: transaction.id + transaction.status + transaction.amount_in_cents + timestamp + events_secret
            $id = $transaction['id'] ?? '';
            $status = $transaction['status'] ?? '';
            $amountInCents = $transaction['amount_in_cents'] ?? '';

            $chain = $id . $status . $amountInCents . $timestamp . $this->eventsSecret;
            $calculated = hash('sha256', $chain);

            return hash_equals($calculated, $receivedChecksum);
        } catch (Exception $e) {
            Log::error('Wompi checksum verification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Process an approved Wompi transaction and renew/activate the tenant's SaaS plan.
     */
    public function processApprovedTransaction(array $transactionData): SubscriptionPayment
    {
        $reference = $transactionData['reference'] ?? '';
        $wompiId = $transactionData['id'] ?? null;
        $status = $transactionData['status'] ?? 'APPROVED';
        $paymentMethodType = $transactionData['payment_method_type'] ?? null;
        $amountInCents = $transactionData['amount_in_cents'] ?? 0;

        /** @var SubscriptionPayment $payment */
        $payment = SubscriptionPayment::where('reference', $reference)->first();

        if (!$payment) {
            // Parse reference if created directly: LIC-SUB-{tenantId}-{planId}-{time}
            if (preg_match('/LIC-SUB-(\d+)-(\d+)/', $reference, $matches)) {
                $tenantId = (int) $matches[1];
                $planId = (int) $matches[2];
                $payment = SubscriptionPayment::create([
                    'tenant_id' => $tenantId,
                    'plan_id' => $planId,
                    'reference' => $reference,
                    'amount_in_cents' => $amountInCents,
                    'currency' => $this->currency,
                    'status' => $status,
                ]);
            } else {
                throw new Exception("Referencia de transacción no reconocida: {$reference}");
            }
        }

        $payment->wompi_transaction_id = $wompiId;
        $payment->status = $status;
        $payment->payment_method_type = $paymentMethodType;
        $payment->paid_at = now();
        $payment->raw_response = $transactionData;
        $payment->save();

        if ($status === 'APPROVED') {
            $tenant = $payment->tenant;
            if ($tenant) {
                // Add 30 days of active subscription
                $currentEnd = ($tenant->subscription_ends_at && $tenant->subscription_ends_at->isFuture())
                    ? $tenant->subscription_ends_at
                    : now();

                $tenant->subscription_status = 'active';
                $tenant->subscription_ends_at = $currentEnd->copy()->addDays(30);
                $tenant->plan_id = $payment->plan_id;
                $tenant->last_payment_reference = $reference;
                $tenant->save();

                AuditService::log(
                    'subscription_renewed',
                    'Tenant',
                    $tenant->id,
                    null,
                    [
                        'plan' => $payment->plan?->name,
                        'amount' => $payment->amount_in_cents / 100,
                        'wompi_id' => $wompiId,
                        'new_expiration' => $tenant->subscription_ends_at->format('Y-m-d H:i:s'),
                    ],
                    "Suscripción renovada exitosamente vía Wompi: 30 días adicionales para {$tenant->name}"
                );
            }
        }

        return $payment;
    }
}
