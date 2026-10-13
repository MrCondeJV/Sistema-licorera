<?php

namespace App\Services;

use App\Models\CashSession;
use App\Models\Sale;
use App\Models\SyncOperation;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class OfflineSyncService
{
    protected SaleService $saleService;

    public function __construct(SaleService $saleService)
    {
        $this->saleService = $saleService;
    }

    /**
     * Process an offline queued sale with strict UUID idempotency.
     */
    public function processOfflineSale(array $payload, string $syncToken, User $user, CashSession $session): array
    {
        $tenantId = app(TenantContext::class)->getTenantId();

        // 1. Idempotency check: see if this sync_token was already processed
        $existing = SyncOperation::where('tenant_id', $tenantId)
            ->where('sync_token', $syncToken)
            ->first();

        if ($existing) {
            if ($existing->status === 'processed' && $existing->result_reference_id) {
                $sale = Sale::with(['items', 'payments'])->find($existing->result_reference_id);
                return [
                    'status' => 'already_processed',
                    'message' => 'Esta operación ya había sido sincronizada previamente.',
                    'sale' => $sale,
                ];
            }
        }

        // 2. Register sync operation record
        $syncOp = SyncOperation::create([
            'tenant_id' => $tenantId,
            'sync_token' => $syncToken,
            'operation_type' => 'sale',
            'payload' => $payload,
            'status' => 'pending',
        ]);

        try {
            $sale = $this->saleService->createSale(
                $payload['items'],
                $payload['payments'],
                $session,
                $user,
                $payload['account_id'] ?? null,
                $payload['customer_id'] ?? null,
                (float) ($payload['discount_total'] ?? 0)
            );

            $syncOp->update([
                'status' => 'processed',
                'result_reference_type' => Sale::class,
                'result_reference_id' => $sale->id,
            ]);

            return [
                'status' => 'synced',
                'message' => 'Venta sincronizada correctamente.',
                'sale' => $sale,
            ];
        } catch (Exception $e) {
            $syncOp->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
