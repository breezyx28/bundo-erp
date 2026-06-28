<?php

namespace App\Models\Concerns;

use App\Models\Branch;
use App\Models\Scopes\BranchScope;
use App\Services\Branch\BranchContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Adds branch ownership to a model:
 *  - Applies the BranchScope global scope for read isolation.
 *  - Auto-fills branch_id on create from the active branch context.
 *  - Exposes the branch() relationship.
 */
trait BelongsToBranch
{
    public static function bootBelongsToBranch(): void
    {
        static::addGlobalScope(new BranchScope);

        static::creating(function ($model) {
            if (! $model->getAttribute($model->getBranchColumn()) && Auth::hasUser()) {
                $branchId = app(BranchContext::class)->currentBranchId();

                if ($branchId) {
                    $model->setAttribute($model->getBranchColumn(), $branchId);
                }
            }
        });
    }

    public function getBranchColumn(): string
    {
        $constant = static::class.'::BRANCH_COLUMN';

        return defined($constant) ? (string) constant($constant) : 'branch_id';
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, $this->getBranchColumn());
    }
}
