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
     * Build checkout payload for Wompi Widget.
     */
    public function getCheckoutData(Tenant $tenant, Plan $plan): array
    {
        $amountInCents = (int) round($plan->price_monthly * 100);
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
            'reference' => $reference,
            'signature' => $signature,
            'redirect_url' => route('billing.wompi-callback'),
            'customer_email' => $tenant->email ?? ($tenant->users()->first()?->email ?? ''),
            'customer_name' => $tenant->name,
            'plan_name' => $plan->name,
            'formatted_amount' => '$' . number_format($plan->price_monthly, 0, ',', '.') . ' COP',
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
