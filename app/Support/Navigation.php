<?php

namespace App\Support;

use App\Services\Modules\ModuleManager;
use App\Services\Tenancy\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class Navigation
{
    public function __construct(
        protected ModuleManager $modules,
        protected TenantContext $tenants,
    ) {}

    /**
     * Build the navigation menu filtered by enabled modules and user permissions.
     *
     * @return Collection<int, array{label:string,icon:string,route:?string}>
     */
    public function menu(): Collection
    {
        if ($this->tenants->isPlatformMode()) {
            return collect(config('platform-navigation', []))->values();
        }

        $user = Auth::user();

        return collect(config('navigation', []))
            ->filter(function (array $item) use ($user) {
                if (! empty($item['module']) && ! $this->modules->isEnabled($item['module'])) {
                    return false;
                }

                if (! empty($item['permission'])) {
                    return $user && $user->can($item['permission']);
                }

                return true;
            })
            ->values();
    }
}
