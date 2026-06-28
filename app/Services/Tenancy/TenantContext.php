<?php

namespace App\Services\Tenancy;

use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Branch\BranchContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Resolves the active tenant for the current request.
 *
 * Regular users inherit tenant_id from their account. Super admins operate in
 * "platform mode" (no tenant) or impersonate a tenant via session.
 */
class TenantContext
{
    public const SESSION_KEY = 'active_tenant_id';

    public function currentTenantId(): ?int
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        if ($user->hasRole('super_admin')) {
            $sessionTenant = Session::get(self::SESSION_KEY);

            return is_numeric($sessionTenant) ? (int) $sessionTenant : null;
        }

        return $user->tenant_id;
    }

    public function currentTenant(): ?Tenant
    {
        $id = $this->currentTenantId();

        return $id ? Tenant::find($id) : null;
    }

    public function isPlatformMode(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $user->hasRole('super_admin')
            && $this->currentTenantId() === null;
    }

    public function isSuperAdmin(): bool
    {
        return Auth::user() instanceof User && Auth::user()->hasRole('super_admin');
    }

    public function setTenant(?int $tenantId): void
    {
        if (! $this->isSuperAdmin()) {
            return;
        }

        if ($tenantId === null) {
            Session::forget(self::SESSION_KEY);
            Session::forget(BranchContext::SESSION_KEY);
        } else {
            Session::put(self::SESSION_KEY, $tenantId);

            $branchId = Branch::query()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->orderBy('id')
                ->value('id');

            if ($branchId) {
                Session::put(BranchContext::SESSION_KEY, $branchId);
            }
        }

        app(BranchContext::class)->flushCache();
    }

    /** @return Collection<int, Tenant> */
    public function allTenants(): Collection
    {
        return Tenant::query()->orderBy('name')->get();
    }
}
