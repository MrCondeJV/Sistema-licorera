<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'is_cash',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_cash' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
