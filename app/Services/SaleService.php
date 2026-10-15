<?php

namespace App\Services;

use App\Models\Account;
use App\Models\CashSession;
use App\Models\Customer;
use App\Models\CustomerDebt;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Table;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class SaleService
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Process a complete sale (either counter quick sale or closing an open account).
     */
    public function createSale(
        array $items,
        array $payments,
        CashSession $cashSession,
        User $user,
        ?int $accountId = null,
        ?int $customerId = null,
        float $discountTotal = 0,
        bool $chargeToDebt = false
    ): Sale {
        if (!$cashSession->isOpen()) {
            throw new Exception("La caja actual no se encuentra abierta. Debe abrir turno de caja antes de vender.");
        }

        if (empty($items)) {
            throw new Exception("No hay productos en la venta.");
        }

        if ($chargeToDebt && empty($customerId)) {
            throw new Exception("Debe seleccionar o asignar un cliente para poder agregar la venta a su cuenta de deuda.");
        }

        return DB::transaction(function () use (
            $items,
            $payments,
            $cashSession,
            $user,
            $accountId,
            $customerId,
            $discountTotal,
            $chargeToDebt
        ) {
            $tenantId = app(TenantContext::class)->getTenantId() 
                ?? session('active_tenant_id') 
                ?? $cashSession->tenant_id 
                ?? \App\Models\Tenant::first()?->id;

            $branchId = app(TenantContext::class)->getBranchId() 
                ?? session('active_branch_id') 
                ?? $cashSession->branch_id 
                ?? \App\Models\Branch::where('tenant_id', $tenantId)->first()?->id;

            $accountToClose = null;
            if ($accountId) {
                /** @var Account $accountToClose */
                $accountToClose = Account::where('id', $accountId)->lockForUpdate()->first();
                if ($accountToClose && $accountToClose->status !== 'open') {
                    throw new Exception("La cuenta de mesa seleccionada ya se encuentra cerrada o cobrada.");
                }
            }

            // 1. Generate unique sequential invoice number for tenant
            $lastSale = Sale::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->latest('id')
                ->first();

            $nextNumber = 1;
            if ($lastSale && preg_match('/POS-(\d+)/', $lastSale->sale_number, $matches)) {
                $nextNumber = ((int) $matches[1]) + 1;
            }
            $saleNumber = 'POS-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

            // 2. Calculate items and validate stock/presentations
            $subtotal = 0;
            $taxTotal = 0;
            $processedItems = [];

            foreach ($items as $itemData) {
                $productId = $itemData['product_id'];
                $quantity = (float) $itemData['quantity'];
                $presentationId = $itemData['product_presentation_id'] ?? null;

                if ($quantity <= 0) {
                    throw new Exception("La cantidad debe ser mayor a 0.");
                }

                /** @var Product $product */
                $product = Product::findOrFail($productId);

                if (!$product->is_active) {
                    throw new Exception("El producto '{$product->name}' se encuentra inactivo.");
                }

                $presentation = null;
                $conversionFactor = 1;
                $unitPrice = (float) $product->price_sell;
                $presentationName = 'Unidad';

                if ($presentationId) {
                    $presentation = ProductPresentation::where('product_id', $product->id)
                        ->where('is_active', true)
                        ->findOrFail($presentationId);

                    $conversionFactor = (int) $presentation->conversion_factor;
                    $unitPrice = (float) $presentation->sale_price;
                    $presentationName = $presentation->name;
                }

                // Enforce liquor rule
                if ($product->is_liquor && $conversionFactor !== 1) {
                    throw new Exception("Los licores solo pueden venderse por botella completa.");
                }

                $itemSubtotal = $unitPrice * $quantity;
                $itemTax = ($itemSubtotal * (float) $product->tax_percentage) / 100;

                $subtotal += $itemSubtotal;
                $taxTotal += $itemTax;

                $processedItems[] = [
                    'product' => $product,
                    'presentation' => $presentation,
                    'presentation_name' => $presentationName,
                    'quantity' => $quantity,
                    'conversion_factor' => $conversionFactor,
                    'unit_cost' => (float) $product->price_buy,
                    'unit_price' => $unitPrice,
                    'subtotal' => $itemSubtotal,
                    'tax_amount' => $itemTax,
                ];
            }

            $total = max(0, $subtotal + $taxTotal - $discountTotal);

            // 3. Validate payments (or customer credit if charged to debt)
            $customer = null;
            if ($chargeToDebt) {
                $customer = Customer::findOrFail($customerId);
                if ($customer->credit_limit > 0 && ($customer->current_debt + $total) > $customer->credit_limit) {
                    $disponible = max(0, $customer->credit_limit - $customer->current_debt);
                    throw new Exception("El cliente {$customer->name} excede su cupo de crédito ($" . number_format($customer->credit_limit, 0, ',', '.') . "). Cupo disponible: $" . number_format($disponible, 0, ',', '.') . " COP.");
                }
            } else {
                $totalPaid = 0;
                foreach ($payments as $pay) {
                    $totalPaid += (float) $pay['amount'];
                }

                // Allow rounding tolerance of 1 peso COP
                if (abs($totalPaid - $total) > 1.0) {
                    throw new Exception("El valor abonado ($" . number_format($totalPaid, 0, ',', '.') . ") no coincide con el total de la venta ($" . number_format($total, 0, ',', '.') . ").");
                }
            }

            // 4. Create Sale Record
            $sale = Sale::create([
                'tenant_id' => $tenantId,
                'branch_id' => $branchId,
                'account_id' => $accountId,
                'user_id' => $user->id,
                'cash_session_id' => $cashSession->id,
                'customer_id' => $customerId,
                'sale_number' => $saleNumber,
                'status' => 'completed',
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'total' => $total,
                'sold_at' => now(),
            ]);

            // 5. Create Sale Items and Deduct Inventory
            foreach ($processedItems as $pItem) {
                SaleItem::create([
                    'tenant_id' => $tenantId,
                    'sale_id' => $sale->id,
                    'product_id' => $pItem['product']->id,
                    'product_presentation_id' => $pItem['presentation']?->id,
                    'product_name_snapshot' => $pItem['product']->name,
                    'presentation_name_snapshot' => $pItem['presentation_name'],
                    'quantity' => $pItem['quantity'],
                    'conversion_factor_snapshot' => $pItem['conversion_factor'],
                    'unit_cost_snapshot' => $pItem['unit_cost'],
                    'unit_price' => $pItem['unit_price'],
                    'subtotal' => $pItem['subtotal'],
                    'tax_amount' => $pItem['tax_amount'],
                ]);

                // Deduct inventory with strict concurrency lock & liquor check in current branch
                $this->inventoryService->deductStock(
                    $pItem['product'],
                    $pItem['quantity'],
                    $pItem['conversion_factor'],
                    'sale',
                    "Venta #{$sale->sale_number}",
                    $user,
                    $sale,
                    $branchId
                );
            }

            // 6. Record Payments OR Register Customer Debt
            if ($chargeToDebt) {
                CustomerDebt::create([
                    'tenant_id' => $tenantId,
                    'customer_id' => $customerId,
                    'sale_id' => $sale->id,
                    'user_id' => $user->id,
                    'original_amount' => $total,
                    'remaining_amount' => $total,
                    'status' => 'pending',
                    'notes' => "Venta {$sale->sale_number} cargada a cuenta",
                    'due_date' => now()->addDays(30),
                ]);

                // Update customer current_debt balance
                $customer->current_debt = (float) $customer->current_debt + $total;
                $customer->save();

                AuditService::log(
                    'customer_debt_charged',
                    'CustomerDebt',
                    $sale->id,
                    null,
                    ['amount' => $total, 'customer' => $customer->name, 'sale_number' => $sale->sale_number],
                    "Venta cargada a cuenta del cliente sin cobro inmediato"
                );
            } else {
                foreach ($payments as $payData) {
                    $paymentMethod = PaymentMethod::findOrFail($payData['payment_method_id']);
                    $amount = (float) $payData['amount'];
                    $received = isset($payData['received_amount']) ? (float) $payData['received_amount'] : $amount;
                    $change = isset($payData['change_amount']) ? (float) $payData['change_amount'] : max(0, $received - $amount);

                    Payment::create([
                        'tenant_id' => $tenantId,
                        'sale_id' => $sale->id,
                        'payment_method_id' => $paymentMethod->id,
                        'amount' => $amount,
                        'received_amount' => $received,
                        'change_amount' => $change,
                        'reference' => $payData['reference'] ?? null,
                        'status' => 'confirmed',
                        'received_by' => $user->id,
                        'paid_at' => now(),
                    ]);
                }
            }

            // 7. If linked to an open Account, close it and free table
            if ($accountToClose) {
                $accountToClose->status = 'closed';
                $accountToClose->closed_by = $user->id;
                $accountToClose->closed_at = now();
                $accountToClose->paid_amount = $total;
                $accountToClose->save();

                if ($accountToClose->table_id) {
                    /** @var Table $table */
                    $table = Table::where('id', $accountToClose->table_id)->lockForUpdate()->first();
                    if ($table) {
                        $table->status = 'free';
                        $table->save();
                    }
                }
            }

            AuditService::log(
                'sale_created',
                'Sale',
                $sale->id,
                null,
                [
                    'sale_number' => $saleNumber,
                    'total' => (float) $total,
                    'item_count' => count($items),
                    'customer' => $customer?->name ?? 'Cliente General',
                    'is_debt' => (bool) $chargeToDebt,
                ],
                "Venta {$saleNumber} por $" . number_format($total, 0, ',', '.') . " COP"
            );

            return $sale;
        });
    }

    /**
     * Process a total or partial refund with audited stock & cash return.
     */
    public function refundSale(Sale $sale, array $refundItems, string $reason, User $user): void
    {
        DB::transaction(function () use ($sale, $refundItems, $reason, $user) {
            $totalRefunded = 0;

            $refund = \App\Models\Refund::create([
                'tenant_id' => $sale->tenant_id,
                'sale_id' => $sale->id,
                'user_id' => $user->id,
                'reason' => $reason,
                'total_refunded' => 0,
                'refunded_at' => now(),
            ]);

            foreach ($refundItems as $rData) {
                /** @var SaleItem $saleItem */
                $saleItem = SaleItem::where('sale_id', $sale->id)->findOrFail($rData['sale_item_id']);
                $qty = (float) $rData['quantity'];

                if ($qty <= 0 || $qty > $saleItem->quantity) {
                    throw new Exception("Cantidad a devolver inválida para '{$saleItem->product_name_snapshot}'.");
                }

                $refundAmount = $qty * (float) $saleItem->unit_price;
                $totalRefunded += $refundAmount;

                \App\Models\RefundItem::create([
                    'tenant_id' => $sale->tenant_id,
                    'refund_id' => $refund->id,
                    'sale_item_id' => $saleItem->id,
                    'product_id' => $saleItem->product_id,
                    'quantity' => $qty,
                    'amount_refunded' => $refundAmount,
                ]);

                // Return stock to inventory in original branch
                $this->inventoryService->addStock(
                    $saleItem->product,
                    $qty,
                    (int) $saleItem->conversion_factor_snapshot,
                    'return',
                    "Devolución de venta #{$sale->sale_number} - {$reason}",
                    $user,
                    $refund,
                    $sale->branch_id
                );
            }

            $refund->total_refunded = $totalRefunded;
            $refund->save();

            // Check if entire sale is refunded
            $allRefunded = ($totalRefunded >= $sale->total);
            $sale->status = $allRefunded ? 'refunded' : 'partially_refunded';
            $sale->save();

            AuditService::log(
                'sale_refunded',
                'Sale',
                $sale->id,
                null,
                ['refund_id' => $refund->id, 'amount' => $totalRefunded],
                $reason
            );
        });
    }
}
