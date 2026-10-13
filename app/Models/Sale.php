<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sale extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'account_id',
        'user_id',
        'cash_session_id',
        'customer_id',
        'sale_number',
        'status',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'sold_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
        'sold_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function calculateTotalCost(): float
    {
        return (float) $this->items->sum(function ($item) {
            return (float) $item->unit_cost_snapshot * (float) $item->quantity * (int) $item->conversion_factor_snapshot;
        });
    }

    public function calculateGrossProfit(): float
    {
        return (float) $this->total - $this->calculateTotalCost();
    }
}
