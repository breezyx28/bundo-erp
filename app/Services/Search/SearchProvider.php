<?php

namespace App\Services\Search;

use Illuminate\Support\Collection;

/**
 * Contract for a searchable source (products, customers, invoices, ...).
 * Implementations MUST respect branch isolation (rely on BranchScope).
 */
interface SearchProvider
{
    public function group(): string;

    /** @return Collection<int, SearchResult> */
    public function search(string $term, int $limit = 5): Collection;
}
