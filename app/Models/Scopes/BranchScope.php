<?php

namespace App\Models\Scopes;

use App\Services\Branch\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Global scope enforcing branch-level data isolation.
 *
 * - Without an authenticated user (CLI, seeders, queue without context) the scope
 *   is a no-op so background work can address all branches explicitly.
 * - With a user, queries are always constrained to the branches they may access.
 * - When a single active branch is selected, results are further narrowed to it.
 */
class BranchScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! Auth::hasUser()) {
            return;
        }

        $context = app(BranchContext::class);
        $column = $model->getTable().'.'.self::columnFor($model);

        // Hard isolation boundary: never leak rows outside the allowed set.
        $allowed = $context->allowedBranchIds()->all();
        $builder->whereIn($column, $allowed ?: [0]);

        // Narrow to the active branch unless the user is in the consolidated view.
        $active = $context->currentBranchId();

        if ($active !== null) {
            $builder->where($column, $active);
        }
    }

    public function extend(Builder $builder): void
    {
        $scope = $this;

        $builder->macro('withoutBranchScope', fn (Builder $query): Builder => $query->withoutGlobalScope($scope));

        $builder->macro('forBranch', function (Builder $query, int $branchId) use ($scope): Builder {
            return $query->withoutGlobalScope($scope)
                ->where($query->getModel()->getTable().'.'.self::columnFor($query->getModel()), $branchId);
        });

        $builder->macro('allBranches', fn (Builder $query): Builder => $query->withoutGlobalScope($scope));
    }

    /** Resolve the branch foreign-key column for a model (honors a BRANCH_COLUMN constant). */
    public static function columnFor(Model $model): string
    {
        $constant = $model::class.'::BRANCH_COLUMN';

        return defined($constant) ? (string) constant($constant) : 'branch_id';
    }
}
