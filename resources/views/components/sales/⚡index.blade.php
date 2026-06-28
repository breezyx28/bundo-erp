<?php

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\SalesInvoice;
use App\Services\Sales\SalesService;
use App\Support\FormSelectCatalog;
use App\Support\Money;
use App\Traits\ConfirmsVoid;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\UiToast;

new #[Layout('components.layouts.app')] #[Title('Sales')] class extends Component
{
    use ConfirmsVoid, UiToast, WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    // Create
    public bool $showForm = false;

    public ?int $customer_id = null;

    public string $sale_type = 'cash';

    public string $invoice_date = '';

    public ?string $due_date = null;

    public string $payment_method = 'cash';

    public float $paid_amount = 0;

    public ?string $discount_type = null;

    public float $discount_value = 0;

    public float $exchange_rate = 0;

    public string $notes = '';

    /** @var array<int, array{product_id:?int, quantity:int, unit_price:float}> */
    public array $items = [];

    // Payment
    public bool $showPayment = false;

    public ?int $payId = null;

    public float $pay_amount = 0;

    public string $pay_method = 'cash';

    public string $pay_date = '';

    public string $pay_reference = '';

    // Detail
    public bool $showDetail = false;

    public ?int $detailId = null;

    /** @var list<array{id:int,name:string}> */
    public array $customerOptions = [];

    /** @var list<array{id:int,name:string}> */
    public array $productOptions = [];

    /** @var array<int, int> */
    public array $stockMap = [];

    public bool $formCatalogsLoaded = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    protected function loadFormCatalogs(): void
    {
        if ($this->formCatalogsLoaded) {
            return;
        }

        $catalog = app(FormSelectCatalog::class);
        $this->customerOptions = $catalog->customers();
        $this->productOptions = $catalog->products(withPrice: true);
        $this->stockMap = $this->stockMap();
        $this->formCatalogsLoaded = true;
    }

    public function canCreate(): bool
    {
        return Gate::allows('invoices.create');
    }

    public function canPay(): bool
    {
        return Gate::allows('payments.create');
    }

    public function canVoid(): bool
    {
        return Gate::allows('invoices.delete');
    }

    public function with(): array
    {
        $totals = $this->computeTotals();

        return [
            'invoices' => SalesInvoice::query()
                ->search($this->search)
                ->status($this->statusFilter)
                ->with(['customer:id,name'])
                ->latest('id')
                ->paginate(10),
            'stockMap' => [],
            'statusOptions' => collect(['paid', 'partial', 'unpaid'])
                ->map(fn ($s) => ['id' => $s, 'name' => __('sales.pay.' . $s)])->all(),
            'methodOptions' => collect(['cash', 'bank_transfer', 'check', 'mobile_money'])
                ->map(fn ($m) => ['id' => $m, 'name' => __('purchasing.methods.' . $m)])->all(),
            'discountTypeOptions' => [
                ['id' => 'percentage', 'name' => '%'],
                ['id' => 'fixed', 'name' => Money::base()],
            ],
            'liveTotals' => $totals,
            'headers' => [
                ['key' => 'invoice_number', 'label' => __('sales.invoice_number')],
                ['key' => 'customer', 'label' => __('nav.customers')],
                ['key' => 'invoice_date', 'label' => __('sales.date')],
                ['key' => 'net_amount', 'label' => __('sales.net'), 'class' => 'text-end'],
                ['key' => 'balance', 'label' => __('sales.balance'), 'class' => 'text-end'],
                ['key' => 'payment_status', 'label' => __('common.status')],
            ],
        ];
    }

    public function money($amount): string
    {
        return Money::format($amount);
    }

    /** @return array<int, int> on-hand per product in the active branch */
    protected function stockMap(): array
    {
        return ProductBatch::query()
            ->selectRaw('product_id, SUM(quantity) as qty')
            ->groupBy('product_id')
            ->pluck('qty', 'product_id')
            ->map(fn ($q) => (int) $q)
            ->all();
    }

    /** @return array{subtotal:float, discount:float, net:float} */
    public function computeTotals(): array
    {
        $subtotal = 0.0;

        foreach ($this->items as $item) {
            $subtotal += (float) ($item['unit_price'] ?? 0) * (int) ($item['quantity'] ?? 0);
        }

        $discount = 0.0;

        if ($this->discount_value > 0 && $this->discount_type) {
            $discount = $this->discount_type === 'percentage'
                ? round($subtotal * min($this->discount_value, 100) / 100, 2)
                : min($this->discount_value, $subtotal);
        }

        return [
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'net' => round($subtotal - $discount, 2),
        ];
    }

    public function updated(string $name, $value): void
    {
        // Auto-fill the unit price from the chosen product's selling price.
        if (preg_match('/^items\.(\d+)\.product_id$/', $name, $m)) {
            $product = Product::find($value);

            if ($product) {
                $this->items[(int) $m[1]]['unit_price'] = (float) $product->selling_price;
            }
        }
    }

    public function openCreate(): void
    {
        $this->loadFormCatalogs();
        $this->reset(['customer_id', 'due_date', 'discount_type', 'notes']);
        $this->sale_type = 'cash';
        $this->payment_method = 'cash';
        $this->paid_amount = 0;
        $this->discount_value = 0;
        $this->invoice_date = now()->toDateString();
        $this->exchange_rate = (float) config('money.default_exchange_rate');
        $this->items = [['product_id' => null, 'quantity' => 1, 'unit_price' => 0]];
        $this->showForm = true;
    }

    public function addItem(): void
    {
        $this->items[] = ['product_id' => null, 'quantity' => 1, 'unit_price' => 0];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save(SalesService $service): void
    {
        $this->authorize('invoices.create');

        $data = $this->validate([
            'customer_id' => 'nullable|integer|exists:customers,id',
            'sale_type' => 'required|in:cash,credit',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'payment_method' => 'required|in:cash,bank_transfer,check,mobile_money',
            'paid_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'exchange_rate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            $service->createInvoice([
                'customer_id' => $data['customer_id'] ?? null,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'sale_type' => $data['sale_type'],
                'discount_type' => $data['discount_type'] ?? null,
                'discount_value' => (float) ($data['discount_value'] ?? 0),
                'exchange_rate' => (float) ($data['exchange_rate'] ?? 0),
                'payment_method' => $data['payment_method'],
                'paid_amount' => (float) ($data['paid_amount'] ?? 0),
                'notes' => $data['notes'] ?? null,
            ], $data['items']);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return;
        }

        $this->showForm = false;
        $this->success(__('sales.invoice_created'));
    }

    public function openPayment(int $id): void
    {
        $invoice = SalesInvoice::findOrFail($id);
        $this->payId = $invoice->id;
        $this->pay_amount = (float) $invoice->balance;
        $this->pay_method = 'cash';
        $this->pay_date = now()->toDateString();
        $this->pay_reference = '';
        $this->showPayment = true;
    }

    public function confirmPayment(SalesService $service): void
    {
        $this->authorize('payments.create');

        $data = $this->validate([
            'pay_amount' => 'required|numeric|min:0.01',
            'pay_method' => 'required|in:cash,bank_transfer,check,mobile_money',
            'pay_date' => 'required|date',
            'pay_reference' => 'nullable|string|max:100',
        ]);

        $invoice = SalesInvoice::findOrFail($this->payId);

        try {
            $service->recordPayment($invoice, [
                'amount' => $data['pay_amount'],
                'payment_method' => $data['pay_method'],
                'payment_date' => $data['pay_date'],
                'reference_number' => $data['pay_reference'] ?: null,
            ]);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return;
        }

        $this->showPayment = false;
        $this->success(__('sales.payment_recorded'));
    }

    public function voidConfirmed(SalesService $service): void
    {
        if ($this->voidId === null) {
            return;
        }

        $this->authorize('invoices.delete');

        try {
            $service->void(SalesInvoice::with('items')->findOrFail($this->voidId));
            $this->cancelVoid();
            $this->success(__('sales.voided'));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }
    }

    public function openDetail(int $id): void
    {
        $this->detailId = $id;
        $this->showDetail = true;
    }

    public function detail(): ?SalesInvoice
    {
        if (! $this->detailId) {
            return null;
        }

        return SalesInvoice::query()
            ->with(['customer:id,name,phone', 'items.product:id,name', 'items.variant', 'payments'])
            ->find($this->detailId);
    }

    public function statusClass(string $status): string
    {
        return match ($status) {
            'paid' => 'badge-success',
            'partial' => 'badge-warning',
            'unpaid' => 'badge-error',
            default => 'badge-ghost',
        };
    }
}; ?>

<div>
    <x-ui.header :title="__('nav.sales')" separator progress-indicator>
        <x-slot:actions>
            @if ($this->canCreate())
                <x-ui.button :label="__('sales.new_sale')" icon="o-plus" class="btn-primary btn-sm" wire:click="openCreate" />
            @endif
        </x-slot:actions>
    </x-ui.header>

    <x-ui.card class="relative">
        <x-ui.table-loading />
        <x-ui.table :headers="$headers" :rows="$invoices" with-pagination>
            <x-slot:toolbar>
                <x-ui.table-filters>
                    <x-ui.input :placeholder="__('common.search')" wire:model.live.debounce.400ms="search" clearable icon="o-magnifying-glass" class="input-sm w-full sm:max-w-xs" />
                    <x-ui.select wire:model.live="statusFilter" :options="$statusOptions" option-value="id" option-label="name"
                        :placeholder="__('common.all')" class="select-sm w-full sm:w-40" />
                </x-ui.table-filters>
            </x-slot:toolbar>
            @scope('cell_customer', $row)
                {{ $row->customer?->name ?? __('sales.walk_in') }}
            @endscope
            @scope('cell_invoice_date', $row)
                <span class="text-xs">{{ $row->invoice_date?->format('Y-m-d') }}</span>
                @if ($row->isOverdue())
                    <x-ui.badge :value="__('sales.overdue')" class="badge-error badge-sm ms-1" />
                @endif
            @endscope
            @scope('cell_net_amount', $row)
                <span class="text-end tabular-nums font-medium">{{ $this->money($row->net_amount) }}</span>
            @endscope
            @scope('cell_balance', $row)
                <span class="text-end tabular-nums {{ $row->balance > 0 ? 'text-error' : 'text-base-content/50' }}">{{ $this->money($row->balance) }}</span>
            @endscope
            @scope('cell_payment_status', $row)
                <x-ui.badge :value="__('sales.pay.' . $row->payment_status)" class="{{ $this->statusClass($row->payment_status) }}" />
            @endscope
            @scope('actions', $row)
                <div class="flex gap-1">
                    <x-ui.button icon="o-eye" wire:click="openDetail({{ $row->id }})" class="btn-text btn-circle btn-sm" />
                    <x-ui.button icon="o-printer" link="{{ route('invoices.print', $row->id) }}" external
                        class="btn-text btn-circle btn-sm" tooltip="{{ __('sales.print') }}" />
                    @if ($row->balance > 0 && $this->canPay())
                        <x-ui.button icon="o-banknotes" wire:click="openPayment({{ $row->id }})"
                            class="btn-text btn-circle btn-sm text-primary" tooltip="{{ __('sales.record_payment') }}" />
                    @endif
                    @if ($this->canVoid())
                        <x-ui.button icon="o-trash" wire:click.stop="confirmVoid({{ $row->id }})"
                            class="btn-text btn-circle btn-sm text-error"
                            tooltip="{{ __('sales.void') }}" />
                    @endif
                </div>
            @endscope
        </x-ui.table>
    </x-ui.card>

    {{-- New sale --}}
    <x-ui.modal wire:model="showForm" :title="__('sales.new_sale')" separator box-class="max-w-4xl">
        <div class="grid gap-4">
            <div class="grid gap-4 sm:grid-cols-4">
                <x-form.search-select :label="__('nav.customers')" wire:model="customer_id" :options="$customerOptions"
                    :placeholder="__('sales.walk_in')" class="sm:col-span-2" />
                <x-ui.select :label="__('sales.sale_type')" wire:model.live="sale_type"
                    :options="[['id' => 'cash', 'name' => __('sales.type.cash')], ['id' => 'credit', 'name' => __('sales.type.credit')]]"
                    option-value="id" option-label="name" />
                <x-ui.input :label="__('sales.date')" wire:model="invoice_date" type="date" />
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium">{{ __('sales.items') }}</span>
                    <x-ui.button :label="__('sales.add_item')" icon="o-plus" class="btn-text btn-circle btn-xs" wire:click="addItem" />
                </div>
                @foreach ($items as $i => $item)
                    <div class="flex items-end gap-2" wire:key="sale-item-{{ $i }}">
                        <div class="flex-1">
                            <x-form.search-select wire:model.live="items.{{ $i }}.product_id" :options="$productOptions"
                                :placeholder="__('nav.products')" />
                            @if (($pid = $item['product_id'] ?? null))
                                <span class="text-xs text-base-content/50">{{ __('inventory.on_hand') }}: {{ $stockMap[$pid] ?? 0 }}</span>
                            @endif
                        </div>
                        <x-ui.input wire:model.live="items.{{ $i }}.quantity" type="number" min="1" class="w-20"
                            :placeholder="__('inventory.quantity')" />
                        <x-ui.input wire:model.live="items.{{ $i }}.unit_price" type="number" step="0.01" min="0" class="w-32"
                            :placeholder="__('fields.selling_price')" />
                        <x-ui.button icon="o-trash" wire:click="removeItem({{ $i }})" class="btn-text btn-circle btn-sm text-error" />
                    </div>
                @endforeach
                @error('items') <span class="text-xs text-error">{{ $message }}</span> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <x-ui.select :label="__('sales.discount')" wire:model.live="discount_type" :options="$discountTypeOptions"
                    option-value="id" option-label="name" :placeholder="__('common.none')" />
                <x-ui.input :label="__('sales.discount_value')" wire:model.live="discount_value" type="number" step="0.01" min="0" />
                <x-ui.input :label="__('sales.exchange_rate')" wire:model="exchange_rate" type="number" step="0.0001" min="0"
                    hint="SDG / USD" />
            </div>

            @if ($sale_type === 'credit')
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.input :label="__('sales.due_date')" wire:model="due_date" type="date" />
                    <x-ui.input :label="__('sales.initial_payment')" wire:model="paid_amount" type="number" step="0.01" min="0" />
                </div>
            @else
                <x-ui.select :label="__('purchasing.method')" wire:model="payment_method" :options="$methodOptions"
                    option-value="id" option-label="name" />
            @endif

            <x-ui.textarea :label="__('fields.notes')" wire:model="notes" rows="2" />

            <div class="rounded-box bg-base-200 p-4 text-sm">
                <div class="flex justify-between"><span>{{ __('sales.subtotal') }}</span><span class="tabular-nums">{{ $this->money($liveTotals['subtotal']) }}</span></div>
                <div class="flex justify-between text-base-content/60"><span>{{ __('sales.discount') }}</span><span class="tabular-nums">- {{ $this->money($liveTotals['discount']) }}</span></div>
                <div class="mt-1 flex justify-between border-t border-base-300 pt-1 font-semibold"><span>{{ __('sales.net') }}</span><span class="tabular-nums">{{ $this->money($liveTotals['net']) }}</span></div>
            </div>
        </div>
        <x-slot:actions>
            <x-ui.button :label="__('common.cancel')" wire:click="$set('showForm', false)" />
            <x-ui.button :label="__('sales.complete_sale')" class="btn-primary" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-ui.modal>

    {{-- Payment --}}
    <x-ui.modal wire:model="showPayment" :title="__('sales.record_payment')" separator box-class="max-w-lg">
        <div class="grid gap-4">
            <x-ui.input :label="__('purchasing.amount')" wire:model="pay_amount" type="number" step="0.01" min="0.01" />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.select :label="__('purchasing.method')" wire:model="pay_method" :options="$methodOptions"
                    option-value="id" option-label="name" />
                <x-ui.input :label="__('purchasing.payment_date')" wire:model="pay_date" type="date" />
            </div>
            <x-ui.input :label="__('purchasing.reference')" wire:model="pay_reference" />
        </div>
        <x-slot:actions>
            <x-ui.button :label="__('common.cancel')" wire:click="$set('showPayment', false)" />
            <x-ui.button :label="__('common.save')" class="btn-primary" wire:click="confirmPayment" spinner="confirmPayment" />
        </x-slot:actions>
    </x-ui.modal>

    {{-- Detail drawer --}}
    <x-ui.drawer wire:model="showDetail" right separator with-close-button class="w-11/12 lg:w-2/5"
        :title="$this->detail()?->invoice_number" :subtitle="__('nav.sales')">
        @if ($inv = $this->detail())
            <div class="space-y-4">
                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.badge :value="__('sales.pay.' . $inv->payment_status)" class="{{ $this->statusClass($inv->payment_status) }}" />
                    <x-ui.badge :value="__('sales.type.' . $inv->sale_type)" class="badge-ghost" />
                    <span class="ms-auto text-sm text-base-content/60">{{ $inv->customer?->name ?? __('sales.walk_in') }}</span>
                </div>

                <div class="divide-y divide-base-300 rounded-box border border-base-300">
                    @foreach ($inv->items as $item)
                        <div class="flex items-center justify-between p-3">
                            <div>
                                <div class="font-medium">{{ $item->product?->name }}</div>
                                <div class="text-xs text-base-content/60">{{ number_format($item->quantity) }} @ {{ $this->money($item->unit_price) }}</div>
                            </div>
                            <span class="tabular-nums font-medium">{{ $this->money($item->total) }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="space-y-1 rounded-box bg-base-200 p-3 text-sm">
                    <div class="flex justify-between"><span>{{ __('sales.net') }}</span><span class="tabular-nums font-semibold">{{ $this->money($inv->net_amount) }}</span></div>
                    <div class="flex justify-between text-success"><span>{{ __('purchasing.paid') }}</span><span class="tabular-nums">{{ $this->money($inv->paid_amount) }}</span></div>
                    <div class="flex justify-between text-error"><span>{{ __('sales.balance') }}</span><span class="tabular-nums">{{ $this->money($inv->balance) }}</span></div>
                </div>

                <div class="flex gap-2">
                    <x-ui.button :label="__('sales.print')" icon="o-printer" link="{{ route('invoices.print', $inv->id) }}" external class="btn-outline btn-sm" />
                    <x-ui.button :label="__('sales.download_pdf')" icon="o-arrow-down-tray" link="{{ route('invoices.pdf', $inv->id) }}" external class="btn-outline btn-sm" />
                </div>
            </div>
        @endif
    </x-ui.drawer>

    <x-ui.void-confirm-modal />
</div>
