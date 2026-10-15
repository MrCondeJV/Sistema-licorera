<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountItem;
use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\Table;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class TableAccountService
{
    /**
     * Open an account for a table or an occasional walk-in customer.
     */
    public function openAccount(
        ?int $tableId = null,
        ?string $clientName = null,
        ?int $customerId = null,
        ?User $user = null
    ): Account {
        $user = $user ?? auth()->user();

        return DB::transaction(function () use ($tableId, $clientName, $customerId, $user) {
            $table = null;
            if ($tableId) {
                /** @var Table $table */
                $table = Table::lockForUpdate()->findOrFail($tableId);

                // Check if table is already occupied
                if ($table->status === 'occupied') {
                    throw new Exception("La mesa {$table->name} ya se encuentra ocupada.");
                }

                // Check in accounts table to ensure no active open account exists
                $existingOpen = Account::where('table_id', $tableId)
                    ->where('status', 'open')
                    ->lockForUpdate()
                    ->exists();

                if ($existingOpen) {
                    throw new Exception("La mesa {$table->name} ya tiene una cuenta abierta activa en el sistema.");
                }

                $table->status = 'occupied';
                $table->save();
            }

            $tenantId = $table?->tenant_id 
                ?? app(TenantContext::class)->getTenantId() 
                ?? session('active_tenant_id') 
                ?? \App\Models\Tenant::first()?->id;

            $branchId = $table?->branch_id 
                ?? app(TenantContext::class)->getBranchId() 
                ?? session('active_branch_id') 
                ?? \App\Models\Branch::where('tenant_id', $tenantId)->first()?->id;

            $account = Account::create([
                'tenant_id' => $tenantId,
                'branch_id' => $branchId,
                'table_id' => $tableId,
                'customer_id' => $customerId,
                'client_name' => $clientName ?: ($table ? "Mesa {$table->name}" : 'Cliente Ocasional'),
                'status' => 'open',
                'opened_by' => $user->id,
                'subtotal' => 0,
                'discount_total' => 0,
                'tax_total' => 0,
                'total' => 0,
                'paid_amount' => 0,
                'opened_at' => now(),
            ]);

            AuditService::log(
                'account_opened',
                'Account',
                $account->id,
                null,
                ['table' => $table?->name, 'client' => $account->client_name],
                "Apertura de cuenta"
            );

            return $account;
        });
    }

    /**
     * Add a product to an open account with price and presentation freeze.
     */
    public function addItem(
        Account $account,
        int $productId,
        ?int $presentationId = null,
        float $quantity = 1
    ): AccountItem {
        return DB::transaction(function () use ($account, $productId, $presentationId, $quantity) {
            /** @var Account $lockedAccount */
            $lockedAccount = Account::where('id', $account->id)->lockForUpdate()->firstOrFail();

            if ($lockedAccount->status !== 'open') {
                throw new Exception("No se pueden agregar productos a una cuenta cerrada.");
            }

            if ($quantity <= 0) {
                throw new Exception("La cantidad debe ser mayor a 0.");
            }

            /** @var Product $product */
            $product = Product::findOrFail($productId);
            if (!$product->is_active) {
                throw new Exception("El producto se encuentra inactivo.");
            }

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

            // Enforce liquor rule: Licores se venden exclusivamente por botella completa
            if ($product->is_liquor && $conversionFactor !== 1) {
                throw new Exception("Los licores solo pueden agregarse por botella completa.");
            }

            // Check if item already exists in account with exact same presentation
            $existingItem = AccountItem::where('account_id', $lockedAccount->id)
                ->where('product_id', $product->id)
                ->where('product_presentation_id', $presentationId)
                ->lockForUpdate()
                ->first();

            if ($existingItem) {
                $existingItem->quantity += $quantity;
                $existingItem->subtotal = $existingItem->quantity * $existingItem->unit_price;
                $existingItem->save();
                $item = $existingItem;
            } else {
                $item = AccountItem::create([
                    'tenant_id' => $lockedAccount->tenant_id,
                    'account_id' => $lockedAccount->id,
                    'product_id' => $product->id,
                    'product_presentation_id' => $presentationId,
                    'product_name_snapshot' => $product->name,
                    'presentation_name_snapshot' => $presentationName,
                    'quantity' => $quantity,
                    'conversion_factor_snapshot' => $conversionFactor,
                    'unit_price' => $unitPrice,
                    'subtotal' => $unitPrice * $quantity,
                ]);
            }

            $lockedAccount->recalculateTotals();

            AuditService::log(
                'table_item_added',
                'Account',
                $lockedAccount->id,
                null,
                [
                    'product' => $product->name,
                    'presentation' => $presentationName,
                    'quantity' => (float) $quantity,
                    'unit_price' => (float) $unitPrice,
                    'table' => $lockedAccount->table?->name ?? 'Mostrador',
                ],
                "Añadido {$quantity}x {$product->name} a Mesa " . ($lockedAccount->table?->name ?? $lockedAccount->client_name)
            );

            return $item;
        });
    }

    /**
     * Remove or decrease an item from an account.
     */
    public function removeItem(Account $account, int $itemId, ?float $qtyToRemove = null): void
    {
        DB::transaction(function () use ($account, $itemId, $qtyToRemove) {
            /** @var Account $lockedAccount */
            $lockedAccount = Account::where('id', $account->id)->lockForUpdate()->firstOrFail();

            if ($lockedAccount->status !== 'open') {
                throw new Exception("No se pueden modificar productos de una cuenta cerrada.");
            }

            $item = AccountItem::where('account_id', $lockedAccount->id)
                ->lockForUpdate()
                ->findOrFail($itemId);

            $deletedQty = ($qtyToRemove === null || $qtyToRemove >= $item->quantity) ? (float) $item->quantity : (float) $qtyToRemove;
            $productName = $item->product_name_snapshot;

            if ($qtyToRemove === null || $qtyToRemove >= $item->quantity) {
                $item->delete();
            } else {
                $item->quantity -= $qtyToRemove;
                $item->subtotal = $item->quantity * $item->unit_price;
                $item->save();
            }

            $lockedAccount->recalculateTotals();

            AuditService::log(
                'table_item_removed',
                'Account',
                $lockedAccount->id,
                null,
                [
                    'product' => $productName,
                    'quantity_removed' => $deletedQty,
                    'table' => $lockedAccount->table?->name ?? 'Mostrador',
                ],
                "Retirado {$deletedQty}x {$productName} de Mesa " . ($lockedAccount->table?->name ?? $lockedAccount->client_name)
            );
        });
    }

    /**
     * Transfer an entire open account to a different table.
     */
    public function transferAccount(Account $account, Table $newTable): void
    {
        if ($account->status !== 'open') {
            throw new Exception("Solo se pueden trasladar cuentas abiertas.");
        }

        DB::transaction(function () use ($account, $newTable) {
            /** @var Table $lockedNewTable */
            $lockedNewTable = Table::lockForUpdate()->findOrFail($newTable->id);

            if ($lockedNewTable->status === 'occupied') {
                throw new Exception("La mesa destino {$lockedNewTable->name} ya se encuentra ocupada.");
            }

            $oldTable = $account->table_id ? Table::find($account->table_id) : null;

            // Free old table
            if ($oldTable) {
                $oldTable->status = 'free';
                $oldTable->save();
            }

            // Occupy new table
            $lockedNewTable->status = 'occupied';
            $lockedNewTable->save();

            $account->table_id = $lockedNewTable->id;
            $account->client_name = "Mesa {$lockedNewTable->name}";
            $account->save();

            AuditService::log(
                'account_transferred',
                'Account',
                $account->id,
                ['old_table' => $oldTable?->name],
                ['new_table' => $lockedNewTable->name],
                "Traslado de mesa completo"
            );
        });
    }

    /**
     * Cancel/void an open account and release table.
     */
    public function cancelAccount(Account $account, string $reason, User $user): void
    {
        if ($account->status !== 'open') {
            throw new Exception("Solo se pueden cancelar cuentas abiertas.");
        }

        DB::transaction(function () use ($account, $reason, $user) {
            $account->status = 'cancelled';
            $account->closed_by = $user->id;
            $account->closed_at = now();
            $account->save();

            if ($account->table_id) {
                $table = Table::find($account->table_id);
                if ($table) {
                    $table->status = 'free';
                    $table->save();
                }
            }

            AuditService::log('account_cancelled', 'Account', $account->id, null, null, $reason);
        });
    }
}
