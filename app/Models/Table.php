<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Table extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'name',
        'status',
        'capacity',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function activeAccount(): HasOne
    {
        return $this->hasOne(Account::class)->where('status', 'open')->latestOfMany();
    }

    public function isFree(): bool
    {
        return $this->status === 'free';
    }

    public function isOccupied(): bool
    {
        return $this->status === 'occupied';
    }

    public function isPendingPayment(): bool
    {
        return $this->status === 'pending_payment';
    }
}
