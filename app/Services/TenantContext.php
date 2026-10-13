<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\Branch;

class TenantContext
{
    protected ?Tenant $tenant = null;
    protected ?Branch $branch = null;
    protected bool $bypassScope = false;

    public function setTenant(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function getTenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function getTenantId(): ?int
    {
        return $this->tenant?->id;
    }

    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }

    public function setBranch(?Branch $branch): void
    {
        $this->branch = $branch;
    }

    public function getBranch(): ?Branch
    {
        return $this->branch;
    }

    public function getBranchId(): ?int
    {
        return $this->branch?->id;
    }

    public function setBypassScope(bool $bypass): void
    {
        $this->bypassScope = $bypass;
    }

    public function shouldBypassScope(): bool
    {
        return $this->bypassScope;
    }
}
