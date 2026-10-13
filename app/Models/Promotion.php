<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'discount_percentage',
        'fixed_price',
        'start_time',
        'end_time',
        'days_of_week',
        'start_date',
        'end_date',
        'min_quantity',
        'is_active',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'fixed_price' => 'decimal:2',
        'days_of_week' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'min_quantity' => 'integer',
        'is_active' => 'boolean',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(PromotionProduct::class);
    }

    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        // Check dates
        if ($this->start_date && $now->toDateString() < $this->start_date->toDateString()) {
            return false;
        }
        if ($this->end_date && $now->toDateString() > $this->end_date->toDateString()) {
            return false;
        }

        // Check day of week
        if (!empty($this->days_of_week)) {
            $dayOfWeek = $now->dayOfWeekIso; // 1 = Mon, 7 = Sun
            if (!in_array($dayOfWeek, $this->days_of_week)) {
                return false;
            }
        }

        // Check time range (Happy hour)
        if ($this->start_time && $this->end_time) {
            $currentTime = $now->format('H:i:s');
            if ($currentTime < $this->start_time || $currentTime > $this->end_time) {
                return false;
            }
        }

        return true;
    }
}
