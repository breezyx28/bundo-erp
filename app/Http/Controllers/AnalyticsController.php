<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithToast;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    use InteractsWithToast;

    public function index(AnalyticsService $service): Response
    {
        return Inertia::render('Analytics/Index', [
            'forecast' => $service->salesForecast(),
            'products' => $service->productPerformance(),
            'customers' => $service->customerAnalysis(),
            'inventory' => $service->inventoryOptimization(),
            'ranking' => $service->branchRanking(),
            'rate' => (float) config('money.default_exchange_rate'),
            'currencies' => [
                'SDG' => $this->currency('SDG'),
                'USD' => $this->currency('USD'),
            ],
        ]);
    }

    public function refresh(AnalyticsService $service): RedirectResponse
    {
        $service->refresh();

        $this->toastSuccess(__('analytics.refreshed'));

        return redirect()->route('analytics.index');
    }

    /**
     * @return array{symbol:string, decimals:int}
     */
    protected function currency(string $code): array
    {
        $config = config("money.currencies.{$code}", ['symbol' => $code, 'decimals' => 2]);

        return ['symbol' => $config['symbol'], 'decimals' => (int) $config['decimals']];
    }
}
