<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'pin_hash',
        'password',
        'is_superadmin',
        'is_active',
    ];

    protected $hidden = [
        'pin_hash',
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'is_superadmin' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function setPin(string $pin): void
    {
        $this->pin_hash = Hash::make($pin);
    }

    public function checkPin(string $pin): bool
    {
        return Hash::check($pin, $this->pin_hash);
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_user')
            ->withPivot('role', 'is_active')
            ->withTimestamps();
    }

    public function tenantUsers(): HasMany
    {
        return $this->hasMany(TenantUser::class);
    }

    public function getRoleInTenant(int $tenantId): ?string
    {
        $membership = $this->tenantUsers()->where('tenant_id', $tenantId)->first();
        return $membership?->role;
    }

    public function hasRoleInTenant(int $tenantId, string|array $roles): bool
    {
        if ($this->is_superadmin) {
            return true;
        }

        $roles = (array) $roles;
        $userRole = $this->getRoleInTenant($tenantId);

        return $userRole !== null && in_array($userRole, $roles, true);
    }
}
