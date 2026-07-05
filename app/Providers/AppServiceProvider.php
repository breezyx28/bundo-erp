<?php

namespace App\Providers;

use App\Services\Branch\BranchContext;
use App\Services\Documents\DocumentNumberService;
use App\Services\Modules\ModuleManager;
use App\Services\Search\GlobalSearch;
use App\Services\Settings\SettingsManager;
use App\Services\Tenancy\TenantContext;
use App\Support\FormSelectCatalog;
use App\Support\Navigation;
use App\Support\TenantBranding;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BranchContext::class);
        $this->app->singleton(TenantContext::class);
        $this->app->singleton(GlobalSearch::class);

        $this->app->singleton(SettingsManager::class);
        $this->app->singleton(ModuleManager::class);
        $this->app->singleton(DocumentNumberService::class);
        $this->app->singleton(Navigation::class);
        $this->app->singleton(TenantBranding::class);
        $this->app->singleton(FormSelectCatalog::class);
        $this->app->singleton(\App\Services\Shop\ShopContext::class);
    }

    public function boot(): void
    {
        Vite::useAggressivePrefetching();

        // System owner bypasses all authorization checks.
        Gate::before(function ($user, string $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });
    }
}
