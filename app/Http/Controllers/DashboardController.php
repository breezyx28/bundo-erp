<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithToast;
use App\Services\Reporting\DashboardService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use InteractsWithToast;

    public function index(DashboardService $service): Response
    {
        return Inertia::render('Dashboard/Index', [
            'kpis' => $service->kpis(),
            'rate' => (float) config('money.default_exchange_rate'),
            'currencies' => $this->currencies(),
        ]);
    }

    public function refresh(DashboardService $service): RedirectResponse
    {
        $service->refresh();

        $this->toastSuccess(__('dashboard.refreshed'));

        return redirect()->route('dashboard');
    }

    /**
     * @return array<string, array{symbol:string, decimals:int}>
     */
    protected function currencies(): array
    {
        $out = [];

        foreach (['SDG', 'USD'] as $code) {
            $config = config("money.currencies.{$code}", ['symbol' => $code, 'decimals' => 2]);
            $out[$code] = ['symbol' => $config['symbol'], 'decimals' => (int) $config['decimals']];
        }

        return $out;
    }
}
