<?php

namespace App\Services\Modules;

use App\Models\Module;
use App\Models\TenantModule;
use App\Services\Branch\BranchContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves which modules are enabled for the current tenant. Drives the
 * dynamic navigation menu and feature gating across the application.
 */
class ModuleManager
{
    public function __construct(protected BranchContext $context) {}

    public function isEnabled(string $key): bool
    {
        return $this->enabledKeys()->contains($key);
    }

    public function enabledKeys(): Collection
    {
        $tenantId = $this->context->currentTenantId();

        if (! $tenantId) {
            // No tenant context (e.g. console): treat all modules as enabled.
            return Module::query()->pluck('key');
        }

        $cacheKey = "modules:enabled:{$tenantId}";

        // Purge legacy entries cached as objects before Laravel 13 serializable_classes enforcement.
        $cached = Cache::get($cacheKey);
        if ($cached instanceof \__PHP_Incomplete_Class || ($cached !== null && ! is_array($cached))) {
            Cache::forget($cacheKey);
        }

        /** @var list<string> $keys */
        $keys = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($tenantId) {
            $overrides = TenantModule::query()
                ->where('tenant_id', $tenantId)
                ->pluck('is_enabled', 'module_id');

            return Module::query()->get()->filter(function (Module $module) use ($overrides) {
                return $overrides->has($module->id)
                    ? (bool) $overrides->get($module->id)
                    : $module->default_enabled;
            })->pluck('key')->values()->all();
        });

        return collect($keys);
    }

    public function flush(): void
    {
        if ($tenantId = $this->context->currentTenantId()) {
            Cache::forget("modules:enabled:{$tenantId}");
        }
    }

    /** @return Collection<int, array{key:string,name:string,is_core:bool,enabled:bool}> */
    public function catalogForTenant(?int $tenantId = null): Collection
    {
        $tenantId ??= $this->context->currentTenantId();

        $overrides = $tenantId
            ? TenantModule::query()->where('tenant_id', $tenantId)->pluck('is_enabled', 'module_id')
            : collect();

        return Module::query()->orderBy('sort_order')->get()->map(function (Module $module) use ($overrides) {
            $enabled = $overrides->has($module->id)
                ? (bool) $overrides->get($module->id)
                : (bool) $module->default_enabled;

            return [
                'key' => $module->key,
                'name' => $module->name,
                'is_core' => (bool) $module->is_core,
                'enabled' => $enabled,
            ];
        });
    }
}
