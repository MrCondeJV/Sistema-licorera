<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'reference',
        'wompi_transaction_id',
        'amount_in_cents',
        'currency',
        'status',
        'payment_method_type',
        'paid_at',
        'raw_response',
    ];

    protected $casts = [
        'amount_in_cents' => 'integer',
        'paid_at' => 'datetime',
        'raw_response' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function getFormattedAmountAttribute(): string
    {
        return '$' . number_format($this->amount_in_cents / 100, 0, ',', '.') . ' ' . $this->currency;
    }

    public function isApproved(): bool
    {
        return $this->status === 'APPROVED';
    }
}
