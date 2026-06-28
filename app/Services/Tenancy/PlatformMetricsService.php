<?php

namespace App\Services\Tenancy;

use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PlatformMetricsService
{
    /** @return array{tenants:int,active_tenants:int,branches:int,users:int,recent_tenants:int} */
    public function summary(): array
    {
        return [
            'tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('is_active', true)->count(),
            'branches' => Branch::count(),
            'users' => User::whereNotNull('tenant_id')->count(),
            'recent_tenants' => Tenant::where('created_at', '>=', now()->subDays(30))->count(),
        ];
    }

    /** @return list<array{id:int,name:string,domain:?string,is_active:bool,branches:int,users:int,onboarded:bool,created_at:string}> */
    public function tenantRows(): array
    {
        $rows = [];

        $tenants = Tenant::query()
            ->withCount(['branches', 'users'])
            ->orderByDesc('created_at')
            ->get();

        foreach ($tenants as $tenant) {
            $rows[] = [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'domain' => $tenant->domain,
                'is_active' => (bool) $tenant->is_active,
                'branches' => (int) $tenant->branches_count,
                'users' => (int) $tenant->users_count,
                'onboarded' => $tenant->onboarding_completed_at !== null,
                'created_at' => $tenant->created_at?->toDateString() ?? '',
            ];
        }

        return $rows;
    }

    /** @return array{db:bool,queue:string,cache:string} */
    public function health(): array
    {
        $dbOk = (bool) rescue(fn () => DB::connection()->getPdo(), false);

        return [
            'db' => $dbOk,
            'queue' => (string) config('queue.default'),
            'cache' => (string) config('cache.default'),
        ];
    }
}
