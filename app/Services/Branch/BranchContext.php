<?php

namespace App\Services\Branch;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Resolves the branch context for the current request.
 *
 * Responsibilities:
 *  - Track which branch the user is "acting in" (or the consolidated "All branches" view).
 *  - Expose the set of branch IDs the user is allowed to read/write (data isolation).
 *  - Provide the active tenant id.
 *
 * Persistence: the active branch id is stored in the session so it survives navigation.
 */
class BranchContext
{
    public const SESSION_KEY = 'active_branch_id';

    /** Runtime override (used by jobs / impersonation / forBranch closures). */
    protected ?int $overrideBranchId = null;

    protected bool $hasOverride = false;

    protected ?Collection $allowedCache = null;

    /**
     * The active branch id, or null when the user is in the consolidated
     * "All branches" view (which shows every branch they are allowed to see).
     */
    public function currentBranchId(): ?int
    {
        if ($this->hasOverride) {
            return $this->overrideBranchId;
        }

        $sessionBranch = Session::get(self::SESSION_KEY);

        if ($sessionBranch === null) {
            return $this->defaultBranchId();
        }

        // "all" is stored as the empty string sentinel for the consolidated view.
        if ($sessionBranch === 'all') {
            return null;
        }

        $branchId = (int) $sessionBranch;

        return $this->allowedBranchIds()->contains($branchId) ? $branchId : $this->defaultBranchId();
    }

    public function currentBranch(): ?Branch
    {
        $id = $this->currentBranchId();

        return $id ? Branch::find($id) : null;
    }

    public function isConsolidated(): bool
    {
        return Session::get(self::SESSION_KEY) === 'all' && $this->canViewAllBranches();
    }

    public function setBranch(int|string|null $branchId): void
    {
        if ($branchId === 'all' || $branchId === null) {
            Session::put(self::SESSION_KEY, $this->canViewAllBranches() ? 'all' : $this->defaultBranchId());

            return;
        }

        if ($this->allowedBranchIds()->contains((int) $branchId)) {
            Session::put(self::SESSION_KEY, (int) $branchId);
        }
    }

    public function currentTenantId(): ?int
    {
        return app(\App\Services\Tenancy\TenantContext::class)->currentTenantId();
    }

    /**
     * Branch IDs the current user may access. Users with the "view all branches"
     * permission see every branch in their tenant; others see only assigned branches.
     */
    public function allowedBranchIds(): Collection
    {
        if ($this->allowedCache !== null) {
            return $this->allowedCache;
        }

        $user = Auth::user();

        if (! $user instanceof User) {
            return $this->allowedCache = collect();
        }

        if ($user->hasRole('super_admin')) {
            $tenantId = app(\App\Services\Tenancy\TenantContext::class)->currentTenantId();

            return $this->allowedCache = $tenantId
                ? Branch::query()->where('tenant_id', $tenantId)->pluck('id')
                : collect();
        }

        if ($this->canViewAllBranches()) {
            return $this->allowedCache = Branch::query()
                ->where('tenant_id', $user->tenant_id)
                ->pluck('id');
        }

        return $this->allowedCache = $user->branches()->pluck('branches.id');
    }

    public function allowedBranches(): Collection
    {
        return Branch::query()->whereIn('id', $this->allowedBranchIds())->orderBy('name')->get();
    }

    public function canViewAllBranches(): bool
    {
        $user = Auth::user();

        if ($user instanceof User && $user->hasRole('super_admin') && app(\App\Services\Tenancy\TenantContext::class)->currentTenantId()) {
            return true;
        }

        return $user instanceof User && $user->can('branches.view_all');
    }

    protected function defaultBranchId(): ?int
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        if ($user->default_branch_id && $this->allowedBranchIds()->contains($user->default_branch_id)) {
            return $user->default_branch_id;
        }

        return $this->allowedBranchIds()->first();
    }

    /**
     * Run a callback as if acting within a specific branch (or all branches when null).
     */
    public function forBranch(?int $branchId, callable $callback): mixed
    {
        $previousOverride = $this->overrideBranchId;
        $previousHasOverride = $this->hasOverride;

        $this->overrideBranchId = $branchId;
        $this->hasOverride = true;

        try {
            return $callback();
        } finally {
            $this->overrideBranchId = $previousOverride;
            $this->hasOverride = $previousHasOverride;
        }
    }

    public function flushCache(): void
    {
        $this->allowedCache = null;
    }
}
