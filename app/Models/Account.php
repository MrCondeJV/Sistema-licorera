<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Account extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'table_id',
        'customer_id',
        'client_name',
        'status',
        'opened_by',
        'closed_by',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'paid_amount',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AccountItem::class);
    }

    public function sale(): HasOne
    {
        return $this->hasOne(Sale::class);
    }

    public function recalculateTotals(): void
    {
        $subtotal = $this->items()->sum('subtotal');
        $this->subtotal = $subtotal;
        $this->total = max(0, $subtotal + $this->tax_total - $this->discount_total);
        $this->save();
    }

    public function getPendingBalance(): float
    {
        return max(0, (float)$this->total - (float)$this->paid_amount);
    }
}
