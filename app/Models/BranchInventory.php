<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchInventory extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'branch_inventories';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'product_id',
        'stock',
        'stock_min',
    ];

    protected $casts = [
        'stock' => 'decimal:2',
        'stock_min' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isLowStock(): bool
    {
        return $this->stock <= $this->stock_min;
    }

    public function isOutOfStock(): bool
    {
        return $this->stock <= 0;
    }
}
