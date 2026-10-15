<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerDebt extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'sale_id',
        'user_id',
        'original_amount',
        'remaining_amount',
        'status',
        'notes',
        'items_snapshot',
        'due_date',
    ];

    protected $casts = [
        'original_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'due_date' => 'date',
        'items_snapshot' => 'array',
    ];

    /**
     * Get the structured list of items/products that compose this debt.
     * Works both from items_snapshot and by fallback to the linked sale items.
     */
    public function getItemsBreakdownAttribute(): array
    {
        if (!empty($this->items_snapshot) && is_array($this->items_snapshot)) {
            return $this->items_snapshot;
        }

        if ($this->sale) {
            $saleItems = $this->sale->relationLoaded('items')
                ? $this->sale->items
                : $this->sale->items()->get();

            if ($saleItems->isNotEmpty()) {
                return $saleItems->map(function ($si) {
                    return [
                        'product_id' => $si->product_id,
                        'product_name' => $si->product_name_snapshot,
                        'presentation_name' => $si->presentation_name_snapshot ?? 'Unidad',
                        'quantity' => (float) $si->quantity,
                        'unit_price' => (float) $si->unit_price,
                        'subtotal' => (float) $si->subtotal,
                    ];
                })->toArray();
            }
        }

        return [];
    }

    /**
     * Human-readable single-line summary of products in the debt.
     */
    public function getItemsSummaryTextAttribute(): string
    {
        $breakdown = $this->items_breakdown;
        if (empty($breakdown)) {
            return $this->notes ?: 'Fiado general';
        }

        $parts = [];
        foreach ($breakdown as $item) {
            $qty = $item['quantity'] ?? 1;
            $name = $item['product_name'] ?? 'Producto';
            $pres = !empty($item['presentation_name']) && $item['presentation_name'] !== 'Unidad'
                ? " ({$item['presentation_name']})"
                : '';
            $parts[] = "{$qty}x {$name}{$pres}";
        }

        return implode(', ', $parts);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class);
    }
}
