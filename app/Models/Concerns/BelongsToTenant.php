<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Services\Branch\BranchContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Adds tenant ownership to a shared (non-branch) model:
 *  - Applies the TenantScope global scope for read isolation.
 *  - Auto-fills tenant_id on create from the current tenant context.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (! $model->getAttribute('tenant_id') && Auth::hasUser()) {
                $model->setAttribute('tenant_id', app(BranchContext::class)->currentTenantId());
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
