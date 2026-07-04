<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Shared date-range and sort handling for list controllers.
 *
 * Sortable columns are whitelisted per call so a client cannot order by an
 * arbitrary (or non-indexed / sensitive) column.
 */
trait AppliesTableFilters
{
    /**
     * Constrain a query to an inclusive [date_from, date_to] range on a column.
     */
    protected function applyDateRange(Builder $query, Request $request, string $column, string $fromKey = 'date_from', string $toKey = 'date_to'): Builder
    {
        $from = (string) $request->string($fromKey);
        $to = (string) $request->string($toKey);

        if ($from !== '') {
            $query->whereDate($column, '>=', $from);
        }

        if ($to !== '') {
            $query->whereDate($column, '<=', $to);
        }

        return $query;
    }

    /**
     * Apply an ordering chosen by the client, restricted to an allow-list.
     *
     * @param  array<int, string>  $sortable  Columns the client may sort by.
     */
    protected function applySort(Builder $query, Request $request, array $sortable, string $defaultColumn = 'id', string $defaultDirection = 'desc'): Builder
    {
        $sort = (string) $request->string('sort');
        $direction = strtolower((string) $request->string('direction')) === 'asc' ? 'asc' : 'desc';

        if ($sort !== '' && in_array($sort, $sortable, true)) {
            return $query->orderBy($sort, $direction);
        }

        return $query->orderBy($defaultColumn, $defaultDirection);
    }

    /**
     * The resolved table-filter values, echoed back to the client so the toolbar
     * can render its current state.
     *
     * @param  array<int, string>  $sortable
     * @return array{sort:?string, direction:string, date_from:?string, date_to:?string}
     */
    protected function tableFilterState(Request $request, array $sortable): array
    {
        $sort = (string) $request->string('sort');

        return [
            'sort' => ($sort !== '' && in_array($sort, $sortable, true)) ? $sort : null,
            'direction' => strtolower((string) $request->string('direction')) === 'asc' ? 'asc' : 'desc',
            'date_from' => (string) $request->string('date_from') ?: null,
            'date_to' => (string) $request->string('date_to') ?: null,
        ];
    }
}
