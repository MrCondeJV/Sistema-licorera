<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'sale_id',
        'product_id',
        'product_presentation_id',
        'product_name_snapshot',
        'presentation_name_snapshot',
        'quantity',
        'conversion_factor_snapshot',
        'unit_cost_snapshot',
        'unit_price',
        'subtotal',
        'tax_amount',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'conversion_factor_snapshot' => 'integer',
        'unit_cost_snapshot' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function presentation(): BelongsTo
    {
        return $this->belongsTo(ProductPresentation::class, 'product_presentation_id');
    }
}
