<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Tenant;
use App\Services\Branch\BranchContext;
use App\Services\Tenancy\TenantContext;

/**
 * Resolves white-label branding for the active tenant/branch scope.
 */
class TenantBranding
{
    public function __construct(
        protected TenantContext $tenants,
        protected BranchContext $branches,
    ) {}

    public function tenant(): ?Tenant
    {
        return $this->tenants->currentTenant();
    }

    public function branch(): ?Branch
    {
        return $this->branches->currentBranch();
    }

    public function primaryColor(): string
    {
        $branch = $this->branch();
        if ($branch instanceof Branch && $branch->primary_color) {
            return $branch->primary_color;
        }

        $tenant = $this->tenant();
        if ($tenant instanceof Tenant && $tenant->primary_color) {
            return $tenant->primary_color;
        }

        return '#39C6A0';
    }

    public function secondaryColor(): string
    {
        $branch = $this->branch();
        if ($branch instanceof Branch && $branch->secondary_color) {
            return $branch->secondary_color;
        }

        $tenant = $this->tenant();
        if ($tenant instanceof Tenant && $tenant->secondary_color) {
            return $tenant->secondary_color;
        }

        return '#228C70';
    }

    public function logoUrl(): ?string
    {
        $branch = $this->branch();
        if ($branch instanceof Branch && $branch->logo) {
            return asset('storage/'.$branch->logo);
        }

        $tenant = $this->tenant();
        if ($tenant instanceof Tenant && $tenant->logo) {
            return asset('storage/'.$tenant->logo);
        }

        return null;
    }

    public function companyName(): string
    {
        $branch = $this->branch();
        if ($branch instanceof Branch) {
            return $branch->name;
        }

        $tenant = $this->tenant();
        if ($tenant instanceof Tenant) {
            return $tenant->name;
        }

        return (string) config('app.name');
    }
}
