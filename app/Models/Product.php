<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'category_id',
        'sku',
        'barcode',
        'name',
        'brand',
        'description',
        'price_buy',
        'price_sell',
        'stock',
        'stock_min',
        'base_unit',
        'is_beer',
        'is_liquor',
        'tax_percentage',
        'image',
        'is_favorite',
        'is_active',
    ];

    protected $casts = [
        'price_buy' => 'decimal:2',
        'price_sell' => 'decimal:2',
        'stock' => 'decimal:2',
        'stock_min' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'is_beer' => 'boolean',
        'is_liquor' => 'boolean',
        'is_favorite' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        if ($this->image) {
            if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
                return $this->image;
            }
            if (str_starts_with($this->image, 'images/')) {
                return asset($this->image);
            }
            return asset('storage/' . $this->image);
        }
        return null;
    }

    public function getSalePriceAttribute(): float
    {
        return (float) $this->price_sell;
    }

    public function getCostPriceAttribute(): float
    {
        return (float) $this->price_buy;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function presentations(): HasMany
    {
        return $this->hasMany(ProductPresentation::class);
    }

    public function activePresentations(): HasMany
    {
        return $this->presentations()->where('is_active', true);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isLowStock(): bool
    {
        return $this->stock <= $this->stock_min;
    }

    public function isOutOfStock(): bool
    {
        return $this->stock <= 0;
    }

    public function branchInventories(): HasMany
    {
        return $this->hasMany(BranchInventory::class);
    }

    public function stockInBranch(?int $branchId = null): float
    {
        $branchId = $branchId ?? app(\App\Services\TenantContext::class)->getBranchId();
        if (!$branchId) {
            return (float) $this->stock;
        }

        $inv = $this->branchInventories()->where('branch_id', $branchId)->first();
        return $inv ? (float) $inv->stock : 0.0;
    }

    public function hasEnoughStock(float $quantity, int $conversionFactor = 1, ?int $branchId = null): bool
    {
        $baseUnitsRequired = $quantity * $conversionFactor;
        $branchId = $branchId ?? app(\App\Services\TenantContext::class)->getBranchId();

        if ($branchId) {
            return $this->stockInBranch($branchId) >= $baseUnitsRequired;
        }

        return $this->stock >= $baseUnitsRequired;
    }
}
