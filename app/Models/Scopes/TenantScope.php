<?php

namespace App\Models\Scopes;

use App\Services\Branch\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Global scope enforcing tenant-level isolation for shared catalog/master data
 * (products, categories, brands, suppliers, customers, ...).
 *
 * No-op without an authenticated user so console/seed work can address all tenants.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! Auth::hasUser()) {
            return;
        }

        $tenantId = app(BranchContext::class)->currentTenantId();

        if ($tenantId !== null) {
            $builder->where($model->getTable().'.tenant_id', $tenantId);
        }
    }

    public function extend(Builder $builder): void
    {
        $scope = $this;

        $builder->macro('withoutTenantScope', fn (Builder $query): Builder => $query->withoutGlobalScope($scope));
    }
}
