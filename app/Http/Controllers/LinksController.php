<?php

namespace App\Http\Controllers;

use App\Services\Reporting\DashboardService;
use App\Services\Tenancy\TenantContext;
use App\Support\Money;
use Inertia\Inertia;
use Inertia\Response;

class LinksController extends Controller
{
    public function index(DashboardService $service, TenantContext $tenantContext): Response
    {
        return Inertia::render('Links/Index', [
            'stats' => $tenantContext->isPlatformMode() ? null : $this->stats($service),
        ]);
    }

    /**
     * Compact stat row for the tablet home page (reuses the cached dashboard KPIs).
     *
     * @return array<int, array{key:string, value:string, tone:string}>
     */
    protected function stats(DashboardService $service): array
    {
        $kpis = $service->kpis();

        return [
            ['key' => 'revenue_month', 'value' => Money::format($kpis['revenue']['month']), 'tone' => 'primary'],
            ['key' => 'profit_month', 'value' => Money::format($kpis['profit']['month']), 'tone' => $kpis['profit']['month'] >= 0 ? 'success' : 'error'],
            ['key' => 'outstanding', 'value' => Money::format($kpis['outstanding']), 'tone' => 'warning'],
            ['key' => 'low_stock', 'value' => (string) $kpis['low_stock'], 'tone' => $kpis['low_stock'] > 0 ? 'error' : 'neutral'],
        ];
    }
}
