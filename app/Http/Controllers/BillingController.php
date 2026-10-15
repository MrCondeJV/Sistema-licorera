<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Services\AuditService;
use App\Services\TenantContext;
use App\Services\WompiService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    protected WompiService $wompiService;

    public function __construct(WompiService $wompiService)
    {
        $this->wompiService = $wompiService;
    }

    /**
     * Display billing overview, current subscription status, plans and payment history.
     */
    public function index()
    {
        $tenantId = app(TenantContext::class)->getTenantId()
            ?? session('active_tenant_id')
            ?? auth()->user()->tenants()->first()?->id;

        $tenant = Tenant::with(['plan', 'subscriptionPayments'])->findOrFail($tenantId);
        $plans = Plan::where('is_active', true)->orderBy('price_monthly')->get();
        foreach ($plans as $p) {
            $p->fee_breakdown = $this->wompiService->calculateAmounts((float) $p->price_monthly);
        }

        $payments = SubscriptionPayment::where('tenant_id', $tenant->id)
            ->with('plan')
            ->latest('id')
            ->paginate(15);

        return view('billing.index', [
            'tenant' => $tenant,
            'currentPlan' => $tenant->plan,
            'plans' => $plans,
            'payments' => $payments,
            'daysRemaining' => $tenant->daysRemaining(),
            'isOnTrial' => $tenant->isOnTrial(),
            'isExpired' => $tenant->isExpired(),
            'isPastDue' => $tenant->isPastDue(),
            'isGracePeriodExceeded' => $tenant->isGracePeriodExceeded(),
            'graceDaysRemaining' => $tenant->graceDaysRemaining(),
            'daysPastDue' => $tenant->daysPastDue(),
            'wompiPublicKey' => config('wompi.public_key'),
            'wompiSandbox' => config('wompi.sandbox'),
        ]);
    }

    /**
     * Generate Wompi integrity signature and reference for Checkout Widget.
     */
    public function getCheckoutData(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $tenantId = app(TenantContext::class)->getTenantId()
            ?? session('active_tenant_id')
            ?? auth()->user()->tenants()->first()?->id;

        $tenant = Tenant::findOrFail($tenantId);
        $plan = Plan::findOrFail($request->input('plan_id'));

        $checkoutData = $this->wompiService->getCheckoutData($tenant, $plan);

        return response()->json([
            'success' => true,
            'data' => $checkoutData,
        ]);
    }

    /**
     * User return URL after completing Wompi widget payment.
     */
    public function wompiCallback(Request $request)
    {
        $transactionId = $request->query('id');

        return view('billing.callback', [
            'transactionId' => $transactionId,
        ]);
    }

    /**
     * Handle Wompi server-to-server Webhook notification (CSRF exempt).
     */
    public function webhook(Request $request)
    {
        Log::info('Wompi Webhook received', ['payload' => $request->all()]);

        $event = $request->input('event');
        $data = $request->input('data');

        if ($event !== 'transaction.updated' || empty($data['transaction'])) {
            return response()->json(['status' => 'ignored', 'message' => 'Evento no procesable'], 200);
        }

        $transaction = $data['transaction'];
        $receivedChecksum = $request->header('x-event-checksum') 
            ?? $request->input('signature.checksum');

        // Verify checksum if in production environment and secret configured
        if (!config('wompi.sandbox') && config('wompi.events_secret') && !empty($receivedChecksum)) {
            $isValid = $this->wompiService->verifyEventChecksum($request->all(), $receivedChecksum);
            if (!$isValid) {
                Log::warning('Wompi Webhook checksum verification failed', ['payload' => $request->all()]);
                return response()->json(['status' => 'error', 'message' => 'Firma inválida'], 403);
            }
        }

        try {
            $payment = $this->wompiService->processApprovedTransaction($transaction);

            return response()->json([
                'status' => 'ok',
                'payment_id' => $payment->id,
                'status_processed' => $payment->status,
            ], 200);
        } catch (Exception $e) {
            Log::error('Wompi Webhook processing error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }
}
