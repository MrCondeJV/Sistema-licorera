<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'price_monthly',
        'max_users',
        'max_branches',
        'max_products',
        'features',
        'is_active',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'max_users' => 'integer',
        'max_branches' => 'integer',
        'max_products' => 'integer',
        'features' => 'array',
        'is_active' => 'boolean',
    ];

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }
}
