<?php

namespace App\Services\Tenancy;

use App\Models\Branch;
use App\Models\Module;
use App\Models\Setting;
use App\Models\StockLocation;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Provisions a new white-label tenant: branding, modules, first branch,
 * admin user, and default settings.
 */
class TenantProvisioningService
{
    /**
     * @param  array{
     *     name:string,
     *     domain?:?string,
     *     database_name?:?string,
     *     primary_color?:string,
     *     secondary_color?:string,
     *     locale?:string,
     *     timezone?:string,
     *     currency?:string,
     *     exchange_rate?:float,
     *     modules?:array<string,bool>,
     *     branch?:array{name:string,code:string,phone?:?string,email?:?string,address?:?string},
     *     admin?:array{name:string,email:string,password:string}
     * }  $data
     */
    public function create(array $data): Tenant
    {
        return DB::transaction(function () use ($data) {
            $tenant = Tenant::create([
                'name' => $data['name'],
                'domain' => $data['domain'] ?? null,
                'database_name' => $data['database_name'] ?? Str::slug($data['name']).'_'.Str::random(4),
                'primary_color' => $data['primary_color'] ?? '#39C6A0',
                'secondary_color' => $data['secondary_color'] ?? '#228C70',
                'is_active' => true,
                'settings' => [
                    'locale' => $data['locale'] ?? 'ar',
                    'timezone' => $data['timezone'] ?? 'Africa/Khartoum',
                ],
            ]);

            $this->syncModules($tenant, $data['modules'] ?? []);
            $this->seedSettings($tenant, $data);

            $branch = null;
            if (! empty($data['branch'])) {
                $branch = $this->createBranch($tenant, $data['branch']);
            }

            if (! empty($data['admin'])) {
                $admin = User::create([
                    'tenant_id' => $tenant->id,
                    'default_branch_id' => $branch?->id,
                    'name' => $data['admin']['name'],
                    'email' => $data['admin']['email'],
                    'password' => Hash::make($data['admin']['password']),
                    'is_active' => true,
                    'settings' => ['locale' => $data['locale'] ?? 'ar'],
                ]);
                $admin->assignRole('admin');

                if ($branch) {
                    $admin->branches()->attach($branch->id, ['is_primary' => true]);
                    $branch->update(['manager_id' => $admin->id]);
                }
            }

            return $tenant->fresh(['branches', 'users']);
        });
    }

    public function updateBranding(Tenant $tenant, array $data): Tenant
    {
        $tenant->update(array_filter([
            'name' => $data['name'] ?? null,
            'domain' => array_key_exists('domain', $data) ? $data['domain'] : null,
            'primary_color' => $data['primary_color'] ?? null,
            'secondary_color' => $data['secondary_color'] ?? null,
            'logo' => $data['logo'] ?? null,
            'is_active' => $data['is_active'] ?? null,
        ], fn ($value) => $value !== null));

        return $tenant->fresh();
    }

    /** @param array<string,bool> $modules */
    public function syncModules(Tenant $tenant, array $modules): void
    {
        $catalog = Module::query()->get()->keyBy('key');

        foreach ($catalog as $key => $module) {
            $enabled = array_key_exists($key, $modules)
                ? (bool) $modules[$key]
                : (bool) $module->default_enabled;

            TenantModule::updateOrCreate(
                ['tenant_id' => $tenant->id, 'module_id' => $module->id],
                ['is_enabled' => $enabled],
            );
        }
    }

    public function completeOnboarding(Tenant $tenant): void
    {
        $tenant->forceFill(['onboarding_completed_at' => now()])->saveQuietly();
    }

    /** @param array<string,mixed> $data */
    protected function seedSettings(Tenant $tenant, array $data): void
    {
        $rows = [
            ['group' => 'general', 'key' => 'locale', 'value' => $data['locale'] ?? 'ar', 'type' => 'string'],
            ['group' => 'general', 'key' => 'timezone', 'value' => $data['timezone'] ?? 'Africa/Khartoum', 'type' => 'string'],
            ['group' => 'currency', 'key' => 'default', 'value' => $data['currency'] ?? 'SDG', 'type' => 'string'],
            ['group' => 'currency', 'key' => 'exchange_rate', 'value' => (string) ($data['exchange_rate'] ?? config('money.default_exchange_rate', 600)), 'type' => 'float'],
        ];

        foreach ($rows as $row) {
            Setting::create([
                'tenant_id' => $tenant->id,
                'branch_id' => null,
                'group' => $row['group'],
                'key' => $row['key'],
                'value' => $row['value'],
                'type' => $row['type'],
            ]);
        }
    }

    /** @param array{name:string,code:string,phone?:?string,email?:?string,address?:?string} $data */
    protected function createBranch(Tenant $tenant, array $data): Branch
    {
        $branch = Branch::create([
            'tenant_id' => $tenant->id,
            'name' => $data['name'],
            'code' => Str::upper($data['code']),
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'primary_color' => $tenant->primary_color,
            'secondary_color' => $tenant->secondary_color,
            'is_active' => true,
        ]);

        StockLocation::create([
            'branch_id' => $branch->id,
            'name' => 'Main Store',
            'code' => 'MAIN',
            'type' => 'store',
            'is_default' => true,
            'is_active' => true,
        ]);

        return $branch;
    }
}
