<?php

namespace App\Http\Controllers;

use App\Services\Reporting\FinancialReportService;
use App\Support\Money;
use App\Support\TenantMoney;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController
{
    public function __invoke(Request $request, FinancialReportService $service): Response|StreamedResponse
    {
        $type = (string) $request->query('type', 'pnl');
        $from = (string) $request->query('from', now()->startOfMonth()->toDateString());
        $to = (string) $request->query('to', now()->toDateString());
        $currency = $request->query('currency') === 'USD' ? 'USD' : 'SDG';
        $format = $request->query('format') === 'pdf' ? 'pdf' : 'csv';

        [$title, $rows] = $this->build($service, $type, $from, $to, $currency);

        $filename = "{$type}_{$from}_{$to}";

        return $format === 'pdf'
            ? $this->pdf($title, $rows, $from, $to, $filename)
            : $this->csv($title, $rows, $filename);
    }

    /**
     * @return array{0:string, 1:list<array{label:string, value:string}>}
     */
    protected function build(FinancialReportService $service, string $type, string $from, string $to, string $currency): array
    {
        $rate = TenantMoney::exchangeRate();
        $fmt = function (float $sdg) use ($currency, $rate): string {
            $value = $currency === 'USD' && $rate > 0 ? round($sdg / $rate, 2) : $sdg;

            return Money::format($value, $currency);
        };

        return match ($type) {
            'cashflow' => $this->cashflowRows($service, $from, $to, $fmt),
            'branches' => $this->branchRows($service, $from, $to, $fmt),
            default => $this->pnlRows($service, $from, $to, $fmt),
        };
    }

    /**
     * @param  callable(float):string  $fmt
     * @return array{0:string, 1:list<array{label:string, value:string}>}
     */
    protected function pnlRows(FinancialReportService $service, string $from, string $to, callable $fmt): array
    {
        $pnl = $service->profitAndLoss($from, $to);

        $rows = [
            ['label' => __('reports.revenue'), 'value' => $fmt($pnl['revenue'])],
            ['label' => __('reports.cogs'), 'value' => $fmt($pnl['cogs'])],
            ['label' => __('reports.gross_profit'), 'value' => $fmt($pnl['gross_profit'])],
            ['label' => __('reports.expenses'), 'value' => $fmt($pnl['expenses'])],
            ['label' => __('reports.net_profit'), 'value' => $fmt($pnl['net_profit'])],
        ];

        foreach ($pnl['expense_breakdown'] as $row) {
            $rows[] = ['label' => '— '.$row['category'], 'value' => $fmt($row['total'])];
        }

        return [__('reports.pnl'), $rows];
    }

    /**
     * @param  callable(float):string  $fmt
     * @return array{0:string, 1:list<array{label:string, value:string}>}
     */
    protected function cashflowRows(FinancialReportService $service, string $from, string $to, callable $fmt): array
    {
        $cf = $service->cashFlow($from, $to);

        return [__('reports.cashflow'), [
            ['label' => __('reports.cash_in'), 'value' => $fmt($cf['cash_in'])],
            ['label' => __('reports.cash_out_payments'), 'value' => $fmt($cf['cash_out_payments'])],
            ['label' => __('reports.cash_out_expenses'), 'value' => $fmt($cf['cash_out_expenses'])],
            ['label' => __('reports.net_cash'), 'value' => $fmt($cf['net'])],
        ]];
    }

    /**
     * @param  callable(float):string  $fmt
     * @return array{0:string, 1:list<array{label:string, value:string}>}
     */
    protected function branchRows(FinancialReportService $service, string $from, string $to, callable $fmt): array
    {
        $rows = [];

        foreach ($service->branchComparison($from, $to) as $row) {
            $rows[] = [
                'label' => $row['branch'],
                'value' => __('reports.revenue').': '.$fmt($row['revenue']).' | '
                    .__('reports.net_profit').': '.$fmt($row['profit']),
            ];
        }

        return [__('reports.branch_comparison'), $rows];
    }

    /**
     * @param  list<array{label:string, value:string}>  $rows
     */
    protected function csv(string $title, array $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($title, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [$title]);
            fputcsv($out, ['Item', 'Value']);
            foreach ($rows as $row) {
                fputcsv($out, [$row['label'], $row['value']]);
            }
            fclose($out);
        }, $filename.'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * @param  list<array{label:string, value:string}>  $rows
     */
    protected function pdf(string $title, array $rows, string $from, string $to, string $filename): Response
    {
        $pdf = Pdf::loadView('pdf.report', [
            'title' => $title,
            'rows' => $rows,
            'from' => $from,
            'to' => $to,
            'generatedAt' => Carbon::now()->format('Y-m-d H:i'),
        ])->setPaper('a4');

        return $pdf->download($filename.'.pdf');
    }
}
