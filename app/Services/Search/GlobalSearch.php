<?php

namespace App\Services\Search;

use Illuminate\Support\Collection;

/**
 * Aggregates results from all registered search providers. Providers are
 * registered by feature modules (e.g. Products, Customers) as they come online.
 */
class GlobalSearch
{
    /** @var array<int, SearchProvider> */
    protected array $providers = [];

    public function register(SearchProvider $provider): void
    {
        $this->providers[] = $provider;
    }

    /** @return Collection<string, Collection<int, SearchResult>> */
    public function search(string $term, int $perGroup = 5): Collection
    {
        $term = trim($term);

        if ($term === '') {
            return collect();
        }

        return collect($this->providers)
            ->mapWithKeys(fn (SearchProvider $provider) => [
                $provider->group() => $provider->search($term, $perGroup),
            ])
            ->filter(fn (Collection $results) => $results->isNotEmpty());
    }

    public function hasProviders(): bool
    {
        return $this->providers !== [];
    }
}
