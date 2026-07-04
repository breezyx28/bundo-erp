<?php

namespace App\Http\Controllers;

use App\Services\Reporting\FinancialReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    /** @var list<string> */
    protected const REPORT_TYPES = ['pnl', 'cashflow', 'branches'];

    public function index(Request $request, FinancialReportService $service): Response
    {
        $type = $this->resolveReportType((string) ($request->string('type') ?: 'pnl'));
        $from = (string) ($request->string('from') ?: now()->startOfMonth()->toDateString());
        $to = (string) ($request->string('to') ?: now()->toDateString());

        return Inertia::render('Reports/Index', [
            'type' => $type,
            'pnl' => $type === 'pnl' ? $service->profitAndLoss($from, $to) : null,
            'cashflow' => $type === 'cashflow' ? $service->cashFlow($from, $to) : null,
            'branches' => $type === 'branches' ? $service->branchComparison($from, $to) : null,
            'typeOptions' => [
                ['value' => 'pnl', 'label' => __('reports.pnl')],
                ['value' => 'cashflow', 'label' => __('reports.cashflow')],
                ['value' => 'branches', 'label' => __('reports.branch_comparison')],
            ],
            'rate' => (float) config('money.default_exchange_rate'),
            'currencies' => [
                'SDG' => $this->currency('SDG'),
                'USD' => $this->currency('USD'),
            ],
            'filters' => [
                'type' => $type,
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    /**
     * @return array{symbol:string, decimals:int}
     */
    protected function currency(string $code): array
    {
        $config = config("money.currencies.{$code}", ['symbol' => $code, 'decimals' => 2]);

        return ['symbol' => $config['symbol'], 'decimals' => (int) $config['decimals']];
    }

    protected function resolveReportType(string $type): string
    {
        return in_array($type, self::REPORT_TYPES, true) ? $type : 'pnl';
    }
}
