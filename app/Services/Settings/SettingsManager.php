<?php

namespace App\Services\Settings;

use App\Models\Setting;
use App\Services\Branch\BranchContext;
use Illuminate\Support\Facades\Cache;

/**
 * Tenant- and branch-aware settings store.
 *
 * Resolution order for reads: branch-specific value first, then tenant-level
 * value, then the provided default. Values are cached per scope.
 */
class SettingsManager
{
    public function __construct(protected BranchContext $context) {}

    public function get(string $key, mixed $default = null, string $group = 'general', ?int $branchId = null): mixed
    {
        $tenantId = $this->context->currentTenantId();
        $branchId = $branchId ?? $this->context->currentBranchId();

        $branchValue = $this->resolve($tenantId, $branchId, $group, $key);
        if ($branchValue !== null) {
            return $branchValue->castValue();
        }

        $tenantValue = $this->resolve($tenantId, null, $group, $key);
        if ($tenantValue !== null) {
            return $tenantValue->castValue();
        }

        return $default;
    }

    public function set(string $key, mixed $value, string $group = 'general', ?int $branchId = null, ?string $type = null): void
    {
        $tenantId = $this->context->currentTenantId();

        $type ??= $this->inferType($value);
        $stored = $type === 'json' ? json_encode($value) : (is_bool($value) ? ($value ? '1' : '0') : (string) $value);

        Setting::updateOrCreate(
            ['tenant_id' => $tenantId, 'branch_id' => $branchId, 'group' => $group, 'key' => $key],
            ['value' => $stored, 'type' => $type],
        );

        $this->forget($tenantId, $branchId, $group, $key);
    }

    protected function resolve(?int $tenantId, ?int $branchId, string $group, string $key): ?Setting
    {
        $cacheKey = $this->cacheKey($tenantId, $branchId, $group, $key);
        $cached = Cache::get($cacheKey);

        if ($cached instanceof \__PHP_Incomplete_Class) {
            Cache::forget($cacheKey);
            $cached = null;
        }

        if ($cached === null) {
            $cached = Cache::remember(
                $cacheKey,
                now()->addHour(),
                function () use ($tenantId, $branchId, $group, $key) {
                    $setting = Setting::query()
                        ->where('tenant_id', $tenantId)
                        ->where('branch_id', $branchId)
                        ->where('group', $group)
                        ->where('key', $key)
                        ->first();

                    if (! $setting) {
                        return false;
                    }

                    return ['value' => $setting->value, 'type' => $setting->type];
                },
            );
        }

        if ($cached === false) {
            return null;
        }

        if (! is_array($cached)) {
            Cache::forget($cacheKey);

            return null;
        }

        $setting = new Setting([
            'tenant_id' => $tenantId,
            'branch_id' => $branchId,
            'group' => $group,
            'key' => $key,
            'value' => $cached['value'],
            'type' => $cached['type'],
        ]);
        $setting->exists = true;

        return $setting;
    }

    protected function forget(?int $tenantId, ?int $branchId, string $group, string $key): void
    {
        Cache::forget($this->cacheKey($tenantId, $branchId, $group, $key));
    }

    protected function cacheKey(?int $tenantId, ?int $branchId, string $group, string $key): string
    {
        return "settings:{$tenantId}:{$branchId}:{$group}:{$key}";
    }

    protected function inferType(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_float($value) => 'float',
            is_array($value) => 'json',
            default => 'string',
        };
    }
}
