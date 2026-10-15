<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\InventoryCount;
use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Deduct stock for a product sale or movement with lockForUpdate to avoid concurrency race conditions.
     * Manages both branch-specific stock (BranchInventory) and aggregate product stock.
     */
    public function deductStock(
        Product $product,
        float $quantity,
        int $conversionFactor = 1,
        string $movementType = 'sale',
        string $reason = 'Venta',
        ?User $user = null,
        $reference = null,
        ?int $branchId = null
    ): StockMovement {
        if ($quantity <= 0) {
            throw new Exception("La cantidad a descontar debe ser mayor a cero.");
        }

        $branchId = $branchId 
            ?? app(TenantContext::class)->getBranchId() 
            ?? session('active_branch_id') 
            ?? Branch::where('tenant_id', $product->tenant_id)->where('is_main', true)->value('id')
            ?? Branch::where('tenant_id', $product->tenant_id)->value('id');

        // Lock product row
        /** @var Product $lockedProduct */
        $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->firstOrFail();

        // Enforce liquor rule: Licores se venden exclusivamente por botella completa
        if ($lockedProduct->is_liquor && $conversionFactor !== 1) {
            throw new Exception("Los licores no admiten fraccionamiento ni presentaciones múltiples. Venta exclusiva por botella completa.");
        }

        $unitsToDeduct = $quantity * $conversionFactor;

        // If a branch is specified, check and deduct branch-level inventory
        if ($branchId) {
            /** @var BranchInventory $branchInv */
            $branchInv = BranchInventory::firstOrCreate(
                [
                    'tenant_id' => $lockedProduct->tenant_id,
                    'branch_id' => $branchId,
                    'product_id' => $lockedProduct->id,
                ],
                [
                    'stock' => $lockedProduct->stock,
                    'stock_min' => $lockedProduct->stock_min,
                ]
            );

            // Lock branch inventory row
            $branchInv = BranchInventory::where('id', $branchInv->id)->lockForUpdate()->firstOrFail();

            if ($branchInv->stock < $unitsToDeduct) {
                $branchName = Branch::find($branchId)?->name ?? "Sucursal #{$branchId}";
                throw new Exception(
                    "Stock insuficiente para '{$lockedProduct->name}' en {$branchName}. Stock actual: {$branchInv->stock}, solicitado: {$unitsToDeduct}."
                );
            }

            $branchInv->stock = (float) $branchInv->stock - $unitsToDeduct;
            $branchInv->save();
        } else {
            if ($lockedProduct->stock < $unitsToDeduct) {
                throw new Exception(
                    "Stock insuficiente para '{$lockedProduct->name}'. Stock actual: {$lockedProduct->stock}, solicitado: {$unitsToDeduct}."
                );
            }
        }

        $stockBefore = (float) $lockedProduct->stock;
        $stockAfter = $stockBefore - $unitsToDeduct;

        $lockedProduct->stock = $stockAfter;
        $lockedProduct->save();

        return StockMovement::create([
            'tenant_id' => $lockedProduct->tenant_id,
            'branch_id' => $branchId,
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
     * Updates both branch-specific stock and aggregate product stock.
     */
    public function addStock(
        Product $product,
        float $quantity,
        int $conversionFactor = 1,
        string $movementType = 'in',
        string $reason = 'Entrada de mercancía',
        ?User $user = null,
        $reference = null,
        ?int $branchId = null
    ): StockMovement {
        if ($quantity <= 0) {
            throw new Exception("La cantidad debe ser mayor a cero.");
        }

        $branchId = $branchId 
            ?? app(TenantContext::class)->getBranchId() 
            ?? session('active_branch_id') 
            ?? Branch::where('tenant_id', $product->tenant_id)->where('is_main', true)->value('id')
            ?? Branch::where('tenant_id', $product->tenant_id)->value('id');

        /** @var Product $lockedProduct */
        $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->firstOrFail();

        $unitsToAdd = $quantity * $conversionFactor;

        if ($branchId) {
            /** @var BranchInventory $branchInv */
            $branchInv = BranchInventory::firstOrCreate(
                [
                    'tenant_id' => $lockedProduct->tenant_id,
                    'branch_id' => $branchId,
                    'product_id' => $lockedProduct->id,
                ],
                [
                    'stock' => 0,
                    'stock_min' => $lockedProduct->stock_min,
                ]
            );

            $branchInv = BranchInventory::where('id', $branchInv->id)->lockForUpdate()->firstOrFail();
            $branchInv->stock = (float) $branchInv->stock + $unitsToAdd;
            $branchInv->save();
        }

        $stockBefore = (float) $lockedProduct->stock;
        $stockAfter = $stockBefore + $unitsToAdd;

        $lockedProduct->stock = $stockAfter;
        $lockedProduct->save();

        return StockMovement::create([
            'tenant_id' => $lockedProduct->tenant_id,
            'branch_id' => $branchId,
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
    public function adjustStock(Product $product, float $newStock, string $reason, User $user, ?int $branchId = null): StockMovement
    {
        if ($newStock < 0) {
            throw new Exception("El stock no puede ser negativo.");
        }

        $branchId = $branchId 
            ?? app(TenantContext::class)->getBranchId() 
            ?? session('active_branch_id') 
            ?? Branch::where('tenant_id', $product->tenant_id)->where('is_main', true)->value('id')
            ?? Branch::where('tenant_id', $product->tenant_id)->value('id');

        return DB::transaction(function () use ($product, $newStock, $reason, $user, $branchId) {
            /** @var Product $lockedProduct */
            $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->firstOrFail();

            if ($branchId) {
                $branchInv = BranchInventory::firstOrCreate(
                    [
                        'tenant_id' => $lockedProduct->tenant_id,
                        'branch_id' => $branchId,
                        'product_id' => $lockedProduct->id,
                    ],
                    [
                        'stock' => $lockedProduct->stock,
                        'stock_min' => $lockedProduct->stock_min,
                    ]
                );

                $branchInv = BranchInventory::where('id', $branchInv->id)->lockForUpdate()->firstOrFail();
                $stockBefore = (float) $branchInv->stock;
                $difference = $newStock - $stockBefore;

                if ($difference == 0) {
                    throw new Exception("El nuevo stock es idéntico al actual en esta sucursal.");
                }

                $branchInv->stock = $newStock;
                $branchInv->save();
            } else {
                $stockBefore = (float) $lockedProduct->stock;
                $difference = $newStock - $stockBefore;

                if ($difference == 0) {
                    throw new Exception("El nuevo stock es idéntico al actual.");
                }
            }

            $lockedProduct->stock = (float) $lockedProduct->stock + $difference;
            $lockedProduct->save();

            $movement = StockMovement::create([
                'tenant_id' => $lockedProduct->tenant_id,
                'branch_id' => $branchId,
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
                ['stock' => $stockBefore, 'branch_id' => $branchId],
                ['stock' => $newStock, 'branch_id' => $branchId],
                $reason
            );

            return $movement;
        });
    }

    /**
     * Inter-branch stock transfer with strict atomicity and tenant isolation.
     * Rollback guaranteed if any part fails.
     */
    public function transferStock(
        Branch $fromBranch,
        Branch $toBranch,
        array $items,
        User $user,
        ?string $notes = null
    ): StockTransfer {
        if ($fromBranch->id === $toBranch->id) {
            throw new Exception("La sucursal de origen y destino no pueden ser la misma.");
        }

        if ($fromBranch->tenant_id !== $toBranch->tenant_id) {
            throw new Exception("No es posible transferir inventario entre negocios diferentes.");
        }

        if (!$fromBranch->is_active) {
            throw new Exception("La sucursal de origen '{$fromBranch->name}' se encuentra desactivada.");
        }

        if (!$toBranch->is_active) {
            throw new Exception("La sucursal de destino '{$toBranch->name}' se encuentra desactivada.");
        }

        if (empty($items)) {
            throw new Exception("Debes especificar al menos un producto a transferir.");
        }

        return DB::transaction(function () use ($fromBranch, $toBranch, $items, $user, $notes) {
            $tenantId = $fromBranch->tenant_id;

            // Generate unique transfer number
            $lastTransfer = StockTransfer::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->latest('id')
                ->first();

            $nextNumber = 1;
            if ($lastTransfer && preg_match('/TRF-(\d+)/', $lastTransfer->transfer_number, $matches)) {
                $nextNumber = ((int) $matches[1]) + 1;
            }
            $transferNumber = 'TRF-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

            $transfer = StockTransfer::create([
                'tenant_id' => $tenantId,
                'from_branch_id' => $fromBranch->id,
                'to_branch_id' => $toBranch->id,
                'user_id' => $user->id,
                'transfer_number' => $transferNumber,
                'status' => 'completed',
                'notes' => $notes,
                'transferred_at' => now(),
            ]);

            foreach ($items as $itemData) {
                $productId = $itemData['product_id'];
                $quantity = (float) $itemData['quantity'];

                if ($quantity <= 0) {
                    throw new Exception("La cantidad a transferir debe ser mayor a cero.");
                }

                /** @var Product $product */
                $product = Product::where('tenant_id', $tenantId)->findOrFail($productId);

                // Lock source branch inventory
                $sourceInv = BranchInventory::firstOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'branch_id' => $fromBranch->id,
                        'product_id' => $product->id,
                    ],
                    [
                        'stock' => $product->stock,
                        'stock_min' => $product->stock_min,
                    ]
                );

                $sourceInv = BranchInventory::where('id', $sourceInv->id)->lockForUpdate()->firstOrFail();

                if ($sourceInv->stock < $quantity) {
                    throw new Exception(
                        "Stock insuficiente en {$fromBranch->name} para transferir {$quantity} unidades de '{$product->name}'. Stock actual: {$sourceInv->stock}."
                    );
                }

                // Deduct from source branch
                $sourceBefore = (float) $sourceInv->stock;
                $sourceInv->stock = $sourceBefore - $quantity;
                $sourceInv->save();

                StockMovement::create([
                    'tenant_id' => $tenantId,
                    'branch_id' => $fromBranch->id,
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                    'movement_type' => 'out',
                    'quantity' => -$quantity,
                    'stock_before' => $sourceBefore,
                    'stock_after' => $sourceInv->stock,
                    'reason' => "Transferencia salida hacia {$toBranch->name} (#{$transfer->transfer_number})",
                    'reference_type' => StockTransfer::class,
                    'reference_id' => $transfer->id,
                ]);

                // Credit destination branch
                $destInv = BranchInventory::firstOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'branch_id' => $toBranch->id,
                        'product_id' => $product->id,
                    ],
                    [
                        'stock' => 0,
                        'stock_min' => $product->stock_min,
                    ]
                );

                $destInv = BranchInventory::where('id', $destInv->id)->lockForUpdate()->firstOrFail();
                $destBefore = (float) $destInv->stock;
                $destInv->stock = $destBefore + $quantity;
                $destInv->save();

                StockMovement::create([
                    'tenant_id' => $tenantId,
                    'branch_id' => $toBranch->id,
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                    'movement_type' => 'in',
                    'quantity' => $quantity,
                    'stock_before' => $destBefore,
                    'stock_after' => $destInv->stock,
                    'reason' => "Transferencia entrada desde {$fromBranch->name} (#{$transfer->transfer_number})",
                    'reference_type' => StockTransfer::class,
                    'reference_id' => $transfer->id,
                ]);

                StockTransferItem::create([
                    'tenant_id' => $tenantId,
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                ]);
            }

            AuditService::log(
                'stock_transfer_completed',
                'StockTransfer',
                $transfer->id,
                null,
                [
                    'transfer_number' => $transfer->transfer_number,
                    'from_branch' => $fromBranch->name,
                    'to_branch' => $toBranch->name,
                    'items_count' => count($items),
                ],
                "Transferencia de inventario de {$fromBranch->name} a {$toBranch->name}"
            );

            return $transfer;
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
                        $user,
                        $count->branch_id
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
