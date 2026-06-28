<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Notifications\NotificationService;
use Illuminate\Console\Command;

/**
 * Walks every tenant and raises low-stock and overdue-debt notifications.
 * Intended to run on the scheduler (daily); safe to run manually too.
 */
class ScanAlerts extends Command
{
    protected $signature = 'notifications:scan {--tenant= : Limit the scan to a single tenant id}';

    protected $description = 'Scan inventory and receivables and raise alerts for affected branches';

    public function handle(NotificationService $notifications): int
    {
        $tenants = Tenant::query()
            ->when($this->option('tenant'), fn ($q) => $q->whereKey($this->option('tenant')))
            ->get(['id']);

        $branchesAlerted = 0;

        foreach ($tenants as $tenant) {
            $branchesAlerted += $notifications->scanLowStock((int) $tenant->id);
            $branchesAlerted += $notifications->scanOverdueDebts((int) $tenant->id);
        }

        $this->info("Alert scan complete. Branch alerts raised: {$branchesAlerted}.");

        return self::SUCCESS;
    }
}
