<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Create a purchase order (draft or received).
     */
    public function createPurchase(
        int $supplierId,
        array $items,
        User $user,
        ?string $invoiceNumber = null,
        ?string $notes = null,
        bool $receiveImmediately = false
    ): Purchase {
        return DB::transaction(function () use (
            $supplierId,
            $items,
            $user,
            $invoiceNumber,
            $notes,
            $receiveImmediately
        ) {
            $supplier = Supplier::findOrFail($supplierId);

            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += (float)$item['quantity'] * (float)$item['unit_cost'];
            }

            $purchase = Purchase::create([
                'tenant_id' => app(TenantContext::class)->getTenantId(),
                'branch_id' => app(TenantContext::class)->getBranchId(),
                'supplier_id' => $supplier->id,
                'user_id' => $user->id,
                'invoice_number' => $invoiceNumber,
                'status' => 'draft',
                'subtotal' => $subtotal,
                'tax_amount' => 0,
                'total' => $subtotal,
                'notes' => $notes,
            ]);

            foreach ($items as $itemData) {
                PurchaseItem::create([
                    'tenant_id' => $purchase->tenant_id,
                    'purchase_id' => $purchase->id,
                    'product_id' => $itemData['product_id'],
                    'product_presentation_id' => $itemData['product_presentation_id'] ?? null,
                    'conversion_factor' => $itemData['conversion_factor'] ?? 1,
                    'quantity' => $itemData['quantity'],
                    'unit_cost' => $itemData['unit_cost'],
                    'subtotal' => (float)$itemData['quantity'] * (float)$itemData['unit_cost'],
                ]);
            }

            if ($receiveImmediately) {
                $this->receivePurchase($purchase, $user);
            }

            return $purchase;
        });
    }

    /**
     * Receive goods from purchase and update stock + weighted average cost.
     */
    public function receivePurchase(Purchase $purchase, User $user): void
    {
        if ($purchase->isReceived()) {
            throw new Exception("Esta compra ya fue recibida anteriormente y su inventario fue actualizado.");
        }

        DB::transaction(function () use ($purchase, $user) {
            /** @var Purchase $lockedPurchase */
            $lockedPurchase = Purchase::lockForUpdate()->findOrFail($purchase->id);

            if ($lockedPurchase->status === 'received') {
                throw new Exception("Esta compra ya fue recibida.");
            }

            foreach ($lockedPurchase->items as $item) {
                /** @var Product $product */
                $product = Product::lockForUpdate()->findOrFail($item->product_id);

                $currentStock = (float) $product->stock;
                $currentCost = (float) $product->price_buy;

                $receivedUnits = (float) $item->quantity * (int) $item->conversion_factor;
                $costPerUnit = (float) $item->unit_cost / (int) $item->conversion_factor;

                // Weighted Average Cost calculation:
                // New Cost = ((current_stock * current_cost) + (received_units * cost_per_unit)) / (current_stock + received_units)
                $totalUnits = $currentStock + $receivedUnits;
                if ($totalUnits > 0) {
                    $newWeightedCost = (($currentStock * $currentCost) + ($receivedUnits * $costPerUnit)) / $totalUnits;
                    $product->price_buy = round($newWeightedCost, 2);
                    $product->save();
                }

                // Add stock via inventory service
                $this->inventoryService->addStock(
                    $product,
                    (float) $item->quantity,
                    (int) $item->conversion_factor,
                    'purchase',
                    "Compra #{$lockedPurchase->id} - Factura: " . ($lockedPurchase->invoice_number ?? 'S/N'),
                    $user,
                    $lockedPurchase
                );
            }

            $lockedPurchase->status = 'received';
            $lockedPurchase->received_at = now();
            $lockedPurchase->save();

            AuditService::log(
                'purchase_received',
                'Purchase',
                $lockedPurchase->id,
                null,
                ['total' => $lockedPurchase->total],
                "Recepción de mercancía y actualización de costo promedio"
            );
        });
    }
}
