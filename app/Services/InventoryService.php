<?php

namespace App\Services;

use App\Models\InventoryCount;
use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\StockMovement;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Deduct stock for a product sale or movement with lockForUpdate to avoid concurrency race conditions.
     */
    public function deductStock(
        Product $product,
        float $quantity,
        int $conversionFactor = 1,
        string $movementType = 'sale',
        string $reason = 'Venta',
        ?User $user = null,
        $reference = null
    ): StockMovement {
        if ($quantity <= 0) {
            throw new Exception("La cantidad a descontar debe ser mayor a cero.");
        }

        // Lock product row
        /** @var Product $lockedProduct */
        $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->firstOrFail();

        // Enforce liquor rule: Licores se venden exclusivamente por botella completa
        if ($lockedProduct->is_liquor && $conversionFactor !== 1) {
            throw new Exception("Los licores no admiten fraccionamiento ni presentaciones múltiples. Venta exclusiva por botella completa.");
        }

        $unitsToDeduct = $quantity * $conversionFactor;

        if ($lockedProduct->stock < $unitsToDeduct) {
            throw new Exception(
                "Stock insuficiente para '{$lockedProduct->name}'. Stock actual: {$lockedProduct->stock}, solicitado: {$unitsToDeduct}."
            );
        }

        $stockBefore = (float) $lockedProduct->stock;
        $stockAfter = $stockBefore - $unitsToDeduct;

        $lockedProduct->stock = $stockAfter;
        $lockedProduct->save();

        return StockMovement::create([
            'tenant_id' => $lockedProduct->tenant_id,
            'branch_id' => app(TenantContext::class)->getBranchId(),
            'product_id' => $lockedProduct->id,
            'user_id' => $user?->id ?? auth()->id(),
            'movement_type' => $movementType,
            'quantity' => -$unitsToDeduct,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'reason' => $reason,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
        ]);
    }

    /**
     * Add stock (Purchases, returns, manual positive adjustments).
     */
    public function addStock(
        Product $product,
        float $quantity,
        int $conversionFactor = 1,
        string $movementType = 'in',
        string $reason = 'Entrada de mercancía',
        ?User $user = null,
        $reference = null
    ): StockMovement {
        if ($quantity <= 0) {
            throw new Exception("La cantidad debe ser mayor a cero.");
        }

        /** @var Product $lockedProduct */
        $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->firstOrFail();

        $unitsToAdd = $quantity * $conversionFactor;
        $stockBefore = (float) $lockedProduct->stock;
        $stockAfter = $stockBefore + $unitsToAdd;

        $lockedProduct->stock = $stockAfter;
        $lockedProduct->save();

        return StockMovement::create([
            'tenant_id' => $lockedProduct->tenant_id,
            'branch_id' => app(TenantContext::class)->getBranchId(),
            'product_id' => $lockedProduct->id,
            'user_id' => $user?->id ?? auth()->id(),
            'movement_type' => $movementType,
            'quantity' => $unitsToAdd,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'reason' => $reason,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
        ]);
    }

    /**
     * Direct audited adjustment (e.g. damages, expiry, physical correction).
     */
    public function adjustStock(Product $product, float $newStock, string $reason, User $user): StockMovement
    {
        if ($newStock < 0) {
            throw new Exception("El stock no puede ser negativo.");
        }

        return DB::transaction(function () use ($product, $newStock, $reason, $user) {
            /** @var Product $lockedProduct */
            $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->firstOrFail();

            $stockBefore = (float) $lockedProduct->stock;
            $difference = $newStock - $stockBefore;

            if ($difference == 0) {
                throw new Exception("El nuevo stock es idéntico al actual.");
            }

            $lockedProduct->stock = $newStock;
            $lockedProduct->save();

            $movement = StockMovement::create([
                'tenant_id' => $lockedProduct->tenant_id,
                'branch_id' => app(TenantContext::class)->getBranchId(),
                'product_id' => $lockedProduct->id,
                'user_id' => $user->id,
                'movement_type' => 'adjustment',
                'quantity' => $difference,
                'stock_before' => $stockBefore,
                'stock_after' => $newStock,
                'reason' => $reason,
            ]);

            AuditService::log(
                'stock_adjusted',
                'Product',
                $lockedProduct->id,
                ['stock' => $stockBefore],
                ['stock' => $newStock],
                $reason
            );

            return $movement;
        });
    }

    /**
     * Apply physical count and reconcile theoretical vs counted inventory.
     */
    public function applyPhysicalCount(InventoryCount $count, User $user): void
    {
        DB::transaction(function () use ($count, $user) {
            foreach ($count->items as $item) {
                if ($item->difference != 0) {
                    $this->adjustStock(
                        $item->product,
                        (float) $item->physical_stock,
                        "Ajuste por conteo físico #{$count->id}",
                        $user
                    );
                }
            }

            $count->status = 'applied';
            $count->applied_at = now();
            $count->save();

            AuditService::log('inventory_count_applied', 'InventoryCount', $count->id, null, null, 'Conteo físico aplicado a kardex');
        });
    }
}
