<?php

use App\Services\Reporting\FinancialReportService;
use App\Support\Money;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.app')] #[Title('Financial Reports')] class extends Component
{
    public string $type = 'pnl';

    public string $from = '';

    public string $to = '';

    public string $currency = 'SDG';

    public function mount(): void
    {
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->toDateString();
    }

    public function with(FinancialReportService $service): array
    {
        return [
            'pnl' => $this->type === 'pnl' ? $service->profitAndLoss($this->from, $this->to) : null,
            'cashflow' => $this->type === 'cashflow' ? $service->cashFlow($this->from, $this->to) : null,
            'branches' => $this->type === 'branches' ? $service->branchComparison($this->from, $this->to) : null,
            'typeOptions' => [
                ['id' => 'pnl', 'name' => __('reports.pnl')],
                ['id' => 'cashflow', 'name' => __('reports.cashflow')],
                ['id' => 'branches', 'name' => __('reports.branch_comparison')],
            ],
            'branchHeaders' => [
                ['key' => 'branch', 'label' => __('reports.branch')],
                ['key' => 'revenue', 'label' => __('reports.revenue'), 'class' => 'text-end'],
                ['key' => 'cogs', 'label' => __('reports.cogs'), 'class' => 'text-end'],
                ['key' => 'expenses', 'label' => __('reports.expenses'), 'class' => 'text-end'],
                ['key' => 'profit', 'label' => __('reports.net_profit'), 'class' => 'text-end'],
            ],
        ];
    }

    public function rate(): float
    {
        return (float) config('money.default_exchange_rate');
    }

    public function fmt(float $sdg): string
    {
        $rate = $this->rate();
        $value = $this->currency === 'USD' && $rate > 0 ? round($sdg / $rate, 2) : $sdg;

        return Money::format($value, $this->currency);
    }

    public function toggleCurrency(): void
    {
        $this->currency = $this->currency === 'SDG' ? 'USD' : 'SDG';
    }

    /** @return array<string, string> */
    public function exportParams(): array
    {
        return ['type' => $this->type, 'from' => $this->from, 'to' => $this->to, 'currency' => $this->currency];
    }
}; ?>

<div class="space-y-6">
    <x-ui.header :title="__('nav.reports')" separator progress-indicator>
        <x-slot:actions>
            <x-ui.button :label="$currency" icon="o-currency-dollar" wire:click="toggleCurrency" class="btn-soft btn-sm" />
            <x-ui.button label="CSV" icon="o-table-cells" link="{{ route('reports.export', ['format' => 'csv'] + $this->exportParams()) }}" external class="btn-text btn-sm" />
            <x-ui.button label="PDF" icon="o-document-arrow-down" link="{{ route('reports.export', ['format' => 'pdf'] + $this->exportParams()) }}" external class="btn-text btn-sm" />
        </x-slot:actions>
    </x-ui.header>

    <x-ui.card>
        <x-slot:menu>
            <x-ui.select wire:model.live="type" :options="$typeOptions" option-value="id" option-label="name" class="select-sm w-full sm:w-56" />
            <x-ui.input wire:model.live="from" type="date" class="input-sm w-full sm:w-40" />
            <x-ui.input wire:model.live="to" type="date" class="input-sm w-full sm:w-40" />
        </x-slot:menu>
        <div class="mb-4 text-sm text-base-content/60">{{ $from }} → {{ $to }}</div>

        @if ($pnl)
            <div class="mx-auto max-w-xl space-y-2">
                <div class="flex justify-between border-b border-base-200 py-2"><span>{{ __('reports.revenue') }}</span><span class="tabular-nums font-medium text-success">{{ $this->fmt($pnl['revenue']) }}</span></div>
                <div class="flex justify-between border-b border-base-200 py-2"><span>{{ __('reports.cogs') }}</span><span class="tabular-nums">- {{ $this->fmt($pnl['cogs']) }}</span></div>
                <div class="flex justify-between border-b border-base-300 py-2 font-semibold"><span>{{ __('reports.gross_profit') }}</span><span class="tabular-nums">{{ $this->fmt($pnl['gross_profit']) }}</span></div>
                <div class="flex justify-between border-b border-base-200 py-2"><span>{{ __('reports.expenses') }}</span><span class="tabular-nums text-error">- {{ $this->fmt($pnl['expenses']) }}</span></div>
                <div class="flex justify-between border-t-2 border-base-content/70 py-2 text-lg font-bold"><span>{{ __('reports.net_profit') }}</span><span class="tabular-nums {{ $pnl['net_profit'] >= 0 ? 'text-success' : 'text-error' }}">{{ $this->fmt($pnl['net_profit']) }}</span></div>

                @if ($pnl['expense_breakdown'])
                    <div class="mt-6">
                        <div class="mb-2 text-sm font-medium">{{ __('reports.expense_breakdown') }}</div>
                        @foreach ($pnl['expense_breakdown'] as $row)
                            <div class="flex justify-between py-1 text-sm"><span>{{ $row['category'] }}</span><span class="tabular-nums">{{ $this->fmt($row['total']) }}</span></div>
                        @endforeach
                    </div>
                @endif
            </div>
        @elseif ($cashflow)
            <div class="mx-auto max-w-xl space-y-2">
                <div class="flex justify-between border-b border-base-200 py-2"><span>{{ __('reports.cash_in') }}</span><span class="tabular-nums text-success">{{ $this->fmt($cashflow['cash_in']) }}</span></div>
                <div class="flex justify-between border-b border-base-200 py-2"><span>{{ __('reports.cash_out_payments') }}</span><span class="tabular-nums text-error">- {{ $this->fmt($cashflow['cash_out_payments']) }}</span></div>
                <div class="flex justify-between border-b border-base-200 py-2"><span>{{ __('reports.cash_out_expenses') }}</span><span class="tabular-nums text-error">- {{ $this->fmt($cashflow['cash_out_expenses']) }}</span></div>
                <div class="flex justify-between border-t-2 border-base-content/70 py-2 text-lg font-bold"><span>{{ __('reports.net_cash') }}</span><span class="tabular-nums {{ $cashflow['net'] >= 0 ? 'text-success' : 'text-error' }}">{{ $this->fmt($cashflow['net']) }}</span></div>
            </div>
        @elseif ($branches !== null)
            <x-ui.table :headers="$branchHeaders" :rows="$branches" show-empty-text :empty-text="__('common.no_results')">
                @scope('cell_branch', $row)
                    <span class="font-medium">{{ $row['branch'] }}</span>
                @endscope
                @scope('cell_revenue', $row)
                    <span class="text-end tabular-nums text-success">{{ $this->fmt($row['revenue']) }}</span>
                @endscope
                @scope('cell_cogs', $row)
                    <span class="text-end tabular-nums">{{ $this->fmt($row['cogs']) }}</span>
                @endscope
                @scope('cell_expenses', $row)
                    <span class="text-end tabular-nums text-error">{{ $this->fmt($row['expenses']) }}</span>
                @endscope
                @scope('cell_profit', $row)
                    <span class="text-end tabular-nums font-semibold {{ $row['profit'] >= 0 ? 'text-success' : 'text-error' }}">{{ $this->fmt($row['profit']) }}</span>
                @endscope
            </x-ui.table>
        @endif
    </x-ui.card>
</div>
