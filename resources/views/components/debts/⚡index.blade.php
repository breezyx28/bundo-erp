<?php

use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Services\Collections\CollectionsService;
use App\Support\Money;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\UiToast;

new #[Layout('components.layouts.app')] #[Title('Debts & Collections')] class extends Component
{
    use UiToast, WithPagination;

    public string $search = '';

    public bool $onlyOverdue = false;

    public string $perfFrom = '';

    public string $perfTo = '';

    // Collect modal
    public bool $showCollect = false;

    public ?int $collectCustomerId = null;

    public string $collectCustomerName = '';

    public float $collect_amount = 0;

    public string $collect_method = 'cash';

    public string $collect_date = '';

    public string $collect_reference = '';

    // Statement drawer
    public bool $showStatement = false;

    public ?int $stmtCustomerId = null;

    public string $stmtCustomerName = '';

    public function mount(): void
    {
        $this->perfFrom = now()->startOfMonth()->toDateString();
        $this->perfTo = now()->toDateString();
        $this->collect_date = now()->toDateString();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedOnlyOverdue(): void
    {
        $this->resetPage();
    }

    public function canManage(): bool
    {
        return Gate::allows('debts.manage');
    }

    public function with(CollectionsService $collections): array
    {
        $aging = collect($collections->aging());

        if ($this->search !== '') {
            $aging = $aging->filter(fn ($r) => str_contains(mb_strtolower($r['customer']), mb_strtolower($this->search)));
        }

        if ($this->onlyOverdue) {
            $aging = $aging->filter(fn ($r) => $r['oldest_days'] > 30);
        }

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 10;
        $items = $aging->forPage($page, $perPage)->values();

        return [
            'summary' => $collections->summary(),
            'aging' => new LengthAwarePaginator(
                $items,
                $aging->count(),
                $perPage,
                $page,
                ['path' => request()->url(), 'pageName' => 'page'],
            ),
            'performance' => $collections->performance($this->perfFrom, $this->perfTo),
            'methodOptions' => collect(['cash', 'bank_transfer', 'check', 'mobile_money'])
                ->map(fn ($m) => ['id' => $m, 'name' => __('purchasing.methods.' . $m)])->all(),
            'agingHeaders' => [
                ['key' => 'customer', 'label' => __('nav.customers')],
                ['key' => 'current', 'label' => __('debts.bucket.current'), 'class' => 'text-end'],
                ['key' => 'd30', 'label' => __('debts.bucket.d30'), 'class' => 'text-end'],
                ['key' => 'd60', 'label' => __('debts.bucket.d60'), 'class' => 'text-end'],
                ['key' => 'd90', 'label' => __('debts.bucket.d90'), 'class' => 'text-end'],
                ['key' => 'total', 'label' => __('purchasing.total'), 'class' => 'text-end'],
            ],
        ];
    }

    public function money($amount): string
    {
        return Money::format($amount);
    }

    public function openCollect(int $customerId, string $name): void
    {
        $this->collectCustomerId = $customerId;
        $this->collectCustomerName = $name;
        $this->collect_amount = (float) SalesInvoice::query()->outstanding()->where('customer_id', $customerId)->sum('balance');
        $this->collect_method = 'cash';
        $this->collect_date = now()->toDateString();
        $this->collect_reference = '';
        $this->showCollect = true;
    }

    public function confirmCollect(CollectionsService $collections): void
    {
        $this->authorize('debts.manage');

        $data = $this->validate([
            'collect_amount' => 'required|numeric|min:0.01',
            'collect_method' => 'required|in:cash,bank_transfer,check,mobile_money',
            'collect_date' => 'required|date',
            'collect_reference' => 'nullable|string|max:100',
        ]);

        try {
            $allocations = $collections->collectFromCustomer($this->collectCustomerId, [
                'amount' => $data['collect_amount'],
                'payment_method' => $data['collect_method'],
                'payment_date' => $data['collect_date'],
                'reference_number' => $data['collect_reference'] ?: null,
            ]);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return;
        }

        $this->showCollect = false;
        $this->success(__('debts.collected', ['count' => count($allocations)]));
    }

    public function openStatement(int $customerId, string $name): void
    {
        $this->stmtCustomerId = $customerId;
        $this->stmtCustomerName = $name;
        $this->showStatement = true;
    }

    /** @return \Illuminate\Support\Collection<int, SalesInvoice> */
    public function statementInvoices()
    {
        if (! $this->stmtCustomerId) {
            return collect();
        }

        return app(CollectionsService::class)->customerInvoices($this->stmtCustomerId);
    }

    public function remind(int $invoiceId, CollectionsService $collections): void
    {
        $this->authorize('debts.manage');
        $invoice = SalesInvoice::findOrFail($invoiceId);
        $collections->markReminded($invoice);
        $this->success(__('debts.reminder_logged'));
    }

    public function whatsappLink(SalesInvoice $invoice): ?string
    {
        $phone = preg_replace('/\D+/', '', (string) $invoice->customer?->phone);

        if (! $phone) {
            return null;
        }

        $message = __('debts.reminder_message', [
            'number' => $invoice->invoice_number,
            'amount' => $this->money($invoice->balance),
            'due' => $invoice->due_date?->format('Y-m-d') ?? '—',
        ]);

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }
}; ?>

<div class="space-y-6">
    <x-ui.header :title="__('nav.debts')" separator progress-indicator />

    {{-- Aging summary --}}
    <x-ui.stats-group>
        <x-ui.stats-row>
            <x-slot:first>
                <x-ui.stat :title="__('debts.outstanding')" :value="$this->money($summary['total'])" icon="o-banknotes" color="text-primary" />
            </x-slot:first>
            <x-slot:second>
                <x-ui.stat :title="__('debts.bucket.current')" :value="$this->money($summary['current'])" icon="o-check-circle" color="text-success" />
            </x-slot:second>
        </x-ui.stats-row>
        <x-ui.stats-break />
        <x-ui.stats-row>
            <x-slot:first>
                <x-ui.stat :title="__('debts.bucket.d30')" :value="$this->money($summary['d30'])" icon="o-clock" color="text-warning" />
            </x-slot:first>
            <x-slot:second>
                <x-ui.stat :title="__('debts.bucket.d60')" :value="$this->money($summary['d60'])" icon="o-exclamation-triangle" color="text-warning" />
            </x-slot:second>
        </x-ui.stats-row>
        <x-ui.stats-break />
        <x-ui.stats-row single>
            <x-ui.stat :title="__('debts.bucket.d90')" :value="$this->money($summary['d90'])" icon="o-fire" color="text-error" />
        </x-ui.stats-row>
    </x-ui.stats-group>

    {{-- Aging table --}}
    <x-ui.card :title="__('debts.aging')" class="relative">
        <x-ui.table-loading />
        <x-ui.table :headers="$agingHeaders" :rows="$aging" with-pagination>
            <x-slot:toolbar>
                <x-ui.table-filters>
                    <x-ui.input :placeholder="__('common.search')" wire:model.live.debounce.400ms="search" clearable icon="o-magnifying-glass" class="input-sm w-full sm:max-w-xs" />
                    <x-ui.toggle :label="__('debts.overdue_only')" wire:model.live="onlyOverdue" class="shrink-0" />
                </x-ui.table-filters>
            </x-slot:toolbar>
            @scope('cell_customer', $row)
                <div class="font-medium">{{ $row['customer'] }}</div>
                @if ($row['oldest_days'] > 0)
                    <div class="text-xs text-base-content/50">{{ __('debts.oldest', ['days' => $row['oldest_days']]) }}</div>
                @endif
            @endscope
            @scope('cell_current', $row)
                <span class="text-end tabular-nums">{{ $this->money($row['current']) }}</span>
            @endscope
            @scope('cell_d30', $row)
                <span class="text-end tabular-nums text-warning">{{ $this->money($row['d30']) }}</span>
            @endscope
            @scope('cell_d60', $row)
                <span class="text-end tabular-nums text-warning">{{ $this->money($row['d60']) }}</span>
            @endscope
            @scope('cell_d90', $row)
                <span class="text-end tabular-nums text-error">{{ $this->money($row['d90']) }}</span>
            @endscope
            @scope('cell_total', $row)
                <span class="text-end tabular-nums font-semibold">{{ $this->money($row['total']) }}</span>
            @endscope
            @scope('actions', $row)
                <x-ui.button icon="o-document-text" wire:click.stop="openStatement({{ $row['customer_id'] }}, @js($row['customer']))"
                    class="btn-text btn-circle btn-sm" tooltip="{{ __('debts.statement') }}" />
                @if ($this->canManage())
                    <x-ui.button icon="o-banknotes" wire:click.stop="openCollect({{ $row['customer_id'] }}, @js($row['customer']))"
                        class="btn-text btn-circle btn-sm text-primary" tooltip="{{ __('debts.collect') }}" />
                @endif
            @endscope
        </x-ui.table>
    </x-ui.card>

    {{-- Collection performance --}}
    <x-ui.card :title="__('debts.performance')">
        <x-slot:menu>
            <x-ui.input wire:model.live="perfFrom" type="date" class="w-40" />
            <x-ui.input wire:model.live="perfTo" type="date" class="w-40" />
        </x-slot:menu>
        <div class="grid gap-4 lg:grid-cols-3">
            <x-ui.stats-group compact>
                <x-ui.stats-row single>
                    <x-ui.stat :title="__('debts.collected')" :value="$this->money($performance['total'])"
                        :description="__('debts.payments_count', ['count' => $performance['count']])" icon="o-arrow-trending-up" color="text-success" />
                </x-ui.stats-row>
            </x-ui.stats-group>
            <div class="lg:col-span-2">
                <div class="mb-2 text-sm font-medium">{{ __('debts.by_branch') }}</div>
                <div class="space-y-1">
                    @forelse ($performance['by_branch'] as $b)
                        <div class="flex items-center justify-between rounded-box border border-base-300 px-3 py-2 text-sm">
                            <span>{{ $b['branch'] }} <span class="text-base-content/40">· {{ $b['count'] }}</span></span>
                            <span class="tabular-nums font-medium">{{ $this->money($b['total']) }}</span>
                        </div>
                    @empty
                        <div class="text-sm text-base-content/50">{{ __('common.no_results') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </x-ui.card>

    {{-- Collect modal --}}
    <x-ui.modal wire:model="showCollect" :title="__('debts.collect') . ' — ' . $collectCustomerName" separator box-class="max-w-lg">
        <div class="grid gap-4">
            <x-ui.input :label="__('purchasing.amount')" wire:model="collect_amount" type="number" step="0.01" min="0.01"
                :hint="__('debts.collect_hint')" />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.select :label="__('purchasing.method')" wire:model="collect_method" :options="$methodOptions"
                    option-value="id" option-label="name" />
                <x-ui.input :label="__('purchasing.payment_date')" wire:model="collect_date" type="date" />
            </div>
            <x-ui.input :label="__('purchasing.reference')" wire:model="collect_reference" />
        </div>
        <x-slot:actions>
            <x-ui.button :label="__('common.cancel')" wire:click="$set('showCollect', false)" />
            <x-ui.button :label="__('debts.collect')" class="btn-primary" wire:click="confirmCollect" spinner="confirmCollect" />
        </x-slot:actions>
    </x-ui.modal>

    {{-- Statement drawer --}}
    <x-ui.drawer wire:model="showStatement" right separator with-close-button class="w-11/12 lg:w-2/5"
        :title="$stmtCustomerName" :subtitle="__('debts.statement')">
        <div class="space-y-2">
            @foreach ($this->statementInvoices() as $inv)
                <div class="rounded-box border border-base-300 p-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <a href="{{ route('invoices.print', $inv->id) }}" target="_blank" class="font-medium link link-hover">{{ $inv->invoice_number }}</a>
                            <div class="text-xs text-base-content/60">
                                {{ __('sales.due_date') }}: {{ $inv->due_date?->format('Y-m-d') ?? '—' }}
                                @if ($inv->daysOverdue() > 0)
                                    · <span class="text-error">{{ __('debts.days_overdue', ['days' => $inv->daysOverdue()]) }}</span>
                                @endif
                            </div>
                        </div>
                        <span class="tabular-nums font-semibold text-error">{{ $this->money($inv->balance) }}</span>
                    </div>
                    @if ($this->canManage())
                        <div class="mt-2 flex gap-2">
                            @if ($url = $this->whatsappLink($inv))
                                <x-ui.button :label="__('debts.whatsapp')" icon="o-chat-bubble-left-right" link="{{ $url }}" external class="btn-text btn-circle btn-xs text-success" />
                            @endif
                            <x-ui.button :label="__('debts.log_reminder')" icon="o-bell" wire:click="remind({{ $inv->id }})" class="btn-text btn-circle btn-xs" />
                            @if ($inv->last_reminder_at)
                                <span class="self-center text-xs text-base-content/40">{{ __('debts.reminded_at', ['time' => $inv->last_reminder_at->diffForHumans()]) }}</span>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </x-ui.drawer>
</div>
