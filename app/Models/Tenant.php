<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'email',
        'phone',
        'tax_id',
        'address',
        'timezone',
        'currency',
        'status',
        'plan_id',
        'trial_ends_at',
        'subscription_status',
        'subscription_ends_at',
        'wompi_customer_email',
        'last_payment_reference',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($tenant) {
            if (empty($tenant->uuid)) {
                $tenant->uuid = (string) Str::uuid();
            }
            if (empty($tenant->slug)) {
                $tenant->slug = Str::slug($tenant->name);
            }
        });
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_user')
            ->withPivot('role', 'is_active')
            ->withTimestamps();
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function tables(): HasMany
    {
        return $this->hasMany(Table::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class);
    }

    public function getSetting(string $key, $default = null)
    {
        $setting = $this->settings()->where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public function setSetting(string $key, $value): void
    {
        $this->settings()->updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) ? json_encode($value) : $value]
        );
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function subscriptionPayments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function isOnTrial(): bool
    {
        return $this->subscription_status === 'trial' 
            && $this->trial_ends_at !== null 
            && $this->trial_ends_at->isFuture();
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscription_status === 'active' 
            && ($this->subscription_ends_at === null || $this->subscription_ends_at->isFuture());
    }

    public function getExpirationDate(): ?\Carbon\Carbon
    {
        if ($this->subscription_status === 'active' && $this->subscription_ends_at) {
            return $this->subscription_ends_at;
        }
        return $this->trial_ends_at;
    }

    public function isPastDue(): bool
    {
        $expiration = $this->getExpirationDate();
        return $expiration ? now()->greaterThan($expiration) : false;
    }

    public function daysPastDue(): int
    {
        $expiration = $this->getExpirationDate();
        if (!$expiration || now()->lessThanOrEqualTo($expiration)) {
            return 0;
        }
        return (int) $expiration->diffInDays(now());
    }

    public function graceDaysRemaining(): int
    {
        if (!$this->isPastDue()) {
            return 5;
        }
        return max(0, 5 - $this->daysPastDue());
    }

    public function isGracePeriodExceeded(): bool
    {
        if (!$this->isPastDue()) {
            return false;
        }
        return $this->daysPastDue() > 5;
    }

    public function hasAccess(): bool
    {
        return $this->isActive() && !$this->isGracePeriodExceeded();
    }

    public function isExpired(): bool
    {
        return $this->isPastDue();
    }

    public function daysRemaining(): int
    {
        if ($this->hasActiveSubscription() && $this->subscription_ends_at) {
            return max(0, (int) now()->diffInDays($this->subscription_ends_at, false));
        }

        if ($this->trial_ends_at) {
            return max(0, (int) now()->diffInDays($this->trial_ends_at, false));
        }

        return 0;
    }
}
