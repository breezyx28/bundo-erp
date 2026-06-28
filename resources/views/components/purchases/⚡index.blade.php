<?php

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Services\Purchasing\PurchaseService;
use App\Support\FormSelectCatalog;
use App\Support\Money;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\UiToast;

new #[Layout('components.layouts.app')] #[Title('Purchase Orders')] class extends Component
{
    use UiToast, WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    // Create / edit
    public bool $showForm = false;

    public ?int $editingId = null;

    public ?int $supplier_id = null;

    public string $order_date = '';

    public ?string $expected_delivery_date = null;

    public string $notes = '';

    /** @var array<int, array{product_id:?int, quantity:int, cost_per_unit:float}> */
    public array $items = [];

    // Receive
    public bool $showReceive = false;

    public ?int $receiveId = null;

    /** @var array<int, int> */
    public array $receiptQty = [];

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
    public array $supplierOptions = [];

    /** @var list<array{id:int,name:string}> */
    public array $productOptions = [];

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
        $this->supplierOptions = $catalog->suppliers();
        $this->productOptions = $catalog->products();
        $this->formCatalogsLoaded = true;
    }

    public function canCreate(): bool
    {
        return Gate::allows('purchases.create');
    }

    public function canReceive(): bool
    {
        return Gate::allows('purchases.receive');
    }

    public function canPay(): bool
    {
        return Gate::allows('payments.create');
    }

    public function with(): array
    {
        return [
            'orders' => PurchaseOrder::query()
                ->search($this->search)
                ->status($this->statusFilter)
                ->with(['supplier:id,name'])
                ->latest('id')
                ->paginate(10),
            'statusOptions' => collect(['draft', 'ordered', 'partial', 'received', 'cancelled'])
                ->map(fn ($s) => ['id' => $s, 'name' => __('purchasing.status.' . $s)])->all(),
            'methodOptions' => collect(['cash', 'bank_transfer', 'check', 'mobile_money'])
                ->map(fn ($m) => ['id' => $m, 'name' => __('purchasing.methods.' . $m)])->all(),
            'headers' => [
                ['key' => 'po_number', 'label' => __('purchasing.po_number')],
                ['key' => 'supplier', 'label' => __('nav.suppliers')],
                ['key' => 'order_date', 'label' => __('purchasing.order_date')],
                ['key' => 'total_amount', 'label' => __('purchasing.total'), 'class' => 'text-end'],
                ['key' => 'order_status', 'label' => __('purchasing.order_status')],
                ['key' => 'payment_status', 'label' => __('purchasing.payment_status')],
            ],
        ];
    }

    public function money($amount): string
    {
        return Money::format($amount);
    }

    public function openCreate(): void
    {
        $this->loadFormCatalogs();
        $this->reset(['editingId', 'supplier_id', 'expected_delivery_date', 'notes']);
        $this->order_date = now()->toDateString();
        $this->items = [['product_id' => null, 'quantity' => 1, 'cost_per_unit' => 0]];
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $order = PurchaseOrder::with('items')->findOrFail($id);

        if (! $order->isEditable()) {
            $this->error(__('purchasing.not_editable'));

            return;
        }

        $this->loadFormCatalogs();
        $this->editingId = $order->id;
        $this->supplier_id = $order->supplier_id;
        $this->order_date = $order->order_date->toDateString();
        $this->expected_delivery_date = $order->expected_delivery_date?->toDateString();
        $this->notes = (string) $order->notes;
        $this->items = $order->items->map(fn ($i) => [
            'product_id' => $i->product_id,
            'quantity' => $i->quantity,
            'cost_per_unit' => (float) $i->cost_per_unit,
        ])->all();
        $this->showForm = true;
    }

    public function addItem(): void
    {
        $this->items[] = ['product_id' => null, 'quantity' => 1, 'cost_per_unit' => 0];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save(PurchaseService $service): void
    {
        $this->authorize($this->editingId ? 'purchases.update' : 'purchases.create');

        $data = $this->validate([
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.cost_per_unit' => 'required|numeric|min:0',
        ]);

        $order = $this->editingId ? PurchaseOrder::findOrFail($this->editingId) : null;

        try {
            $service->save([
                'supplier_id' => $data['supplier_id'],
                'order_date' => $data['order_date'],
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'notes' => $data['notes'] ?? null,
            ], $data['items'], $order);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return;
        }

        $this->showForm = false;
        $this->success($this->editingId ? __('common.updated') : __('common.created'));
    }

    public function place(int $id, PurchaseService $service): void
    {
        $this->authorize('purchases.update');
        $this->runGuarded(fn () => $service->place(PurchaseOrder::findOrFail($id)), __('purchasing.placed'));
    }

    public function cancel(int $id, PurchaseService $service): void
    {
        $this->authorize('purchases.update');
        $this->runGuarded(fn () => $service->cancel(PurchaseOrder::with('items')->findOrFail($id)), __('purchasing.cancelled'));
    }

    public function openReceive(int $id): void
    {
        $order = PurchaseOrder::with('items.product:id,name')->findOrFail($id);
        $this->receiveId = $order->id;
        $this->receiptQty = $order->items->mapWithKeys(fn ($i) => [$i->id => $i->outstandingQuantity()])->all();
        $this->showReceive = true;
    }

    public function confirmReceive(PurchaseService $service): void
    {
        $this->authorize('purchases.receive');

        $order = PurchaseOrder::with('items')->findOrFail($this->receiveId);

        try {
            $service->receive($order, array_map('intval', $this->receiptQty));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return;
        }

        $this->showReceive = false;
        $this->success(__('purchasing.received'));
    }

    public function openPayment(int $id): void
    {
        $order = PurchaseOrder::findOrFail($id);
        $this->payId = $order->id;
        $this->pay_amount = $order->outstanding();
        $this->pay_method = 'cash';
        $this->pay_date = now()->toDateString();
        $this->pay_reference = '';
        $this->showPayment = true;
    }

    public function confirmPayment(PurchaseService $service): void
    {
        $this->authorize('payments.create');

        $data = $this->validate([
            'pay_amount' => 'required|numeric|min:0.01',
            'pay_method' => 'required|in:cash,bank_transfer,check,mobile_money',
            'pay_date' => 'required|date',
            'pay_reference' => 'nullable|string|max:100',
        ]);

        $order = PurchaseOrder::findOrFail($this->payId);

        try {
            $service->recordPayment($order, [
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
        $this->success(__('purchasing.payment_recorded'));
    }

    public function detailForReceive(): ?PurchaseOrder
    {
        if (! $this->receiveId) {
            return null;
        }

        return PurchaseOrder::with('items.product:id,name')->find($this->receiveId);
    }

    public function openDetail(int $id): void
    {
        $this->detailId = $id;
        $this->showDetail = true;
    }

    public function detail(): ?PurchaseOrder
    {
        if (! $this->detailId) {
            return null;
        }

        return PurchaseOrder::query()
            ->with(['supplier:id,name', 'items.product:id,name', 'items.variant', 'payments'])
            ->find($this->detailId);
    }

    protected function runGuarded(callable $action, string $successMessage): void
    {
        try {
            $action();
            $this->success($successMessage);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }
    }

    public function orderStatusClass(string $status): string
    {
        return match ($status) {
            'draft' => 'badge-ghost',
            'ordered' => 'badge-info',
            'partial' => 'badge-warning',
            'received' => 'badge-success',
            'cancelled' => 'badge-error',
            default => 'badge-ghost',
        };
    }

    public function paymentStatusClass(string $status): string
    {
        return match ($status) {
            'unpaid' => 'badge-error',
            'partial' => 'badge-warning',
            'paid' => 'badge-success',
            default => 'badge-ghost',
        };
    }
}; ?>

<div>
    <x-ui.header :title="__('nav.purchases')" separator progress-indicator>
        <x-slot:actions>
            @if ($this->canCreate())
                <x-ui.button :label="__('purchasing.new_po')" icon="o-plus" class="btn-primary btn-sm" wire:click="openCreate" />
            @endif
        </x-slot:actions>
    </x-ui.header>

    <x-ui.card class="relative">
        <x-ui.table-loading />
        <x-ui.table :headers="$headers" :rows="$orders" with-pagination>
            <x-slot:toolbar>
                <x-ui.table-filters>
                    <x-ui.input :placeholder="__('common.search')" wire:model.live.debounce.400ms="search" clearable icon="o-magnifying-glass" class="input-sm w-full sm:max-w-xs" />
                    <x-ui.select wire:model.live="statusFilter" :options="$statusOptions" option-value="id" option-label="name"
                        :placeholder="__('common.all')" class="select-sm w-full sm:w-40" />
                </x-ui.table-filters>
            </x-slot:toolbar>
            @scope('cell_supplier', $row)
                {{ $row->supplier?->name }}
            @endscope
            @scope('cell_order_date', $row)
                <span class="text-xs">{{ $row->order_date?->format('Y-m-d') }}</span>
            @endscope
            @scope('cell_total_amount', $row)
                <span class="text-end tabular-nums font-medium">{{ $this->money($row->total_amount) }}</span>
            @endscope
            @scope('cell_order_status', $row)
                <x-ui.badge :value="__('purchasing.status.' . $row->order_status)" class="{{ $this->orderStatusClass($row->order_status) }}" />
            @endscope
            @scope('cell_payment_status', $row)
                <x-ui.badge :value="__('purchasing.pay.' . $row->payment_status)" class="{{ $this->paymentStatusClass($row->payment_status) }}" />
            @endscope
            @scope('actions', $row)
                <div class="flex gap-1">
                    <x-ui.button icon="o-eye" wire:click="openDetail({{ $row->id }})" class="btn-text btn-circle btn-sm" />
                    @if ($row->isEditable() && $this->canCreate())
                        <x-ui.button icon="o-pencil" wire:click="edit({{ $row->id }})" class="btn-text btn-circle btn-sm" />
                    @endif
                    @if ($row->order_status === 'draft' && $this->canCreate())
                        <x-ui.button icon="o-check-circle" wire:click="place({{ $row->id }})"
                            class="btn-ghost btn-sm text-info" tooltip="{{ __('purchasing.place') }}" />
                    @endif
                    @if ($row->isReceivable() && $this->canReceive())
                        <x-ui.button icon="o-arrow-down-tray" wire:click="openReceive({{ $row->id }})"
                            class="btn-text btn-circle btn-sm text-success" tooltip="{{ __('purchasing.receive') }}" />
                    @endif
                    @if ($row->payment_status !== 'paid' && $row->order_status !== 'cancelled' && $this->canPay())
                        <x-ui.button icon="o-banknotes" wire:click="openPayment({{ $row->id }})"
                            class="btn-text btn-circle btn-sm text-primary" tooltip="{{ __('purchasing.record_payment') }}" />
                    @endif
                </div>
            @endscope
        </x-ui.table>
    </x-ui.card>

    {{-- Create / edit --}}
    <x-ui.modal wire:model="showForm" :title="$editingId ? __('purchasing.edit_po') : __('purchasing.new_po')" separator box-class="max-w-3xl">
        <div class="grid gap-4">
            <div class="grid gap-4 sm:grid-cols-3">
                <x-form.search-select :label="__('nav.suppliers')" wire:model="supplier_id" :options="$supplierOptions"
                    :placeholder="__('common.none')" />
                <x-ui.input :label="__('purchasing.order_date')" wire:model="order_date" type="date" />
                <x-ui.input :label="__('purchasing.expected_delivery')" wire:model="expected_delivery_date" type="date" />
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium">{{ __('purchasing.items') }}</span>
                    <x-ui.button :label="__('purchasing.add_item')" icon="o-plus" class="btn-text btn-circle btn-xs" wire:click="addItem" />
                </div>
                @foreach ($items as $i => $item)
                    <div class="flex items-end gap-2" wire:key="po-item-{{ $i }}">
                        <x-form.search-select wire:model="items.{{ $i }}.product_id" :options="$productOptions"
                            :placeholder="__('nav.products')" class="flex-1" />
                        <x-ui.input wire:model="items.{{ $i }}.quantity" type="number" min="1" class="w-24"
                            :placeholder="__('purchasing.qty')" />
                        <x-ui.input wire:model="items.{{ $i }}.cost_per_unit" type="number" step="0.01" min="0" class="w-32"
                            :placeholder="__('fields.cost_price')" />
                        <x-ui.button icon="o-trash" wire:click="removeItem({{ $i }})" class="btn-text btn-circle btn-sm text-error" />
                    </div>
                @endforeach
                @error('items') <span class="text-xs text-error">{{ $message }}</span> @enderror
            </div>

            <x-ui.textarea :label="__('fields.notes')" wire:model="notes" rows="2" />
        </div>
        <x-slot:actions>
            <x-ui.button :label="__('common.cancel')" wire:click="$set('showForm', false)" />
            <x-ui.button :label="__('common.save')" class="btn-primary" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-ui.modal>

    {{-- Receive --}}
    <x-ui.modal wire:model="showReceive" :title="__('purchasing.receive_stock')" separator box-class="max-w-2xl">
        @if ($r = $this->detailForReceive())
            <div class="space-y-2">
                @foreach ($r->items as $item)
                    <div class="flex items-center justify-between gap-3 rounded-box border border-base-300 p-3" wire:key="rcv-{{ $item->id }}">
                        <div class="flex-1">
                            <div class="font-medium">{{ $item->product?->name }}</div>
                            <div class="text-xs text-base-content/60">
                                {{ __('purchasing.outstanding') }}: {{ $item->outstandingQuantity() }} / {{ $item->quantity }}
                            </div>
                        </div>
                        <x-ui.input wire:model="receiptQty.{{ $item->id }}" type="number" min="0"
                            :max="$item->outstandingQuantity()" class="w-28" />
                    </div>
                @endforeach
            </div>
        @endif
        <x-slot:actions>
            <x-ui.button :label="__('common.cancel')" wire:click="$set('showReceive', false)" />
            <x-ui.button :label="__('purchasing.receive')" class="btn-primary" wire:click="confirmReceive" spinner="confirmReceive" />
        </x-slot:actions>
    </x-ui.modal>

    {{-- Payment --}}
    <x-ui.modal wire:model="showPayment" :title="__('purchasing.record_payment')" separator box-class="max-w-lg">
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
        :title="$this->detail()?->po_number" :subtitle="__('nav.purchases')">
        @if ($o = $this->detail())
            <div class="space-y-4">
                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.badge :value="__('purchasing.status.' . $o->order_status)" class="{{ $this->orderStatusClass($o->order_status) }}" />
                    <x-ui.badge :value="__('purchasing.pay.' . $o->payment_status)" class="{{ $this->paymentStatusClass($o->payment_status) }}" />
                    <span class="ms-auto text-sm text-base-content/60">{{ $o->supplier?->name }}</span>
                </div>

                <div class="divide-y divide-base-300 rounded-box border border-base-300">
                    @foreach ($o->items as $item)
                        <div class="flex items-center justify-between p-3">
                            <div>
                                <div class="font-medium">{{ $item->product?->name }}</div>
                                <div class="text-xs text-base-content/60">
                                    {{ number_format($item->received_quantity) }} / {{ number_format($item->quantity) }}
                                    @ {{ $this->money($item->cost_per_unit) }}
                                </div>
                            </div>
                            <span class="tabular-nums font-medium">{{ $this->money($item->total) }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-between rounded-box bg-base-200 p-3 font-semibold">
                    <span>{{ __('purchasing.total') }}</span>
                    <span class="tabular-nums">{{ $this->money($o->total_amount) }}</span>
                </div>
                <div class="flex justify-between px-3 text-sm">
                    <span class="text-base-content/60">{{ __('purchasing.paid') }}</span>
                    <span class="tabular-nums text-success">{{ $this->money($o->paid_amount) }}</span>
                </div>
                <div class="flex justify-between px-3 text-sm">
                    <span class="text-base-content/60">{{ __('purchasing.outstanding') }}</span>
                    <span class="tabular-nums text-error">{{ $this->money($o->outstanding()) }}</span>
                </div>

                @if ($o->payments->isNotEmpty())
                    <div>
                        <div class="mb-2 text-sm font-medium">{{ __('purchasing.payments') }}</div>
                        <div class="space-y-1">
                            @foreach ($o->payments as $p)
                                <div class="flex items-center justify-between rounded-box border border-base-300 px-3 py-2 text-sm">
                                    <span>{{ $p->payment_date?->format('Y-m-d') }} · {{ __('purchasing.methods.' . $p->payment_method) }}</span>
                                    <span class="tabular-nums">{{ $this->money($p->amount) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </x-ui.drawer>
</div>
