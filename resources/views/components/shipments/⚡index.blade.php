<?php

use App\Models\LogisticsCompany;
use App\Models\SalesInvoice;
use App\Models\Shipment;
use App\Models\ShipmentReturn;
use App\Services\Shipping\ShippingService;
use App\Support\FormSelectCatalog;
use App\Support\Money;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Traits\UiToast;

new #[Layout('components.layouts.app')] #[Title('Shipments')] class extends Component
{
    use UiToast, WithFileUploads, WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $from = '';

    public string $to = '';

    // Create
    public bool $showForm = false;

    public ?int $sales_invoice_id = null;

    public ?int $logistics_company_id = null;

    public string $dispatch_city = '';

    public string $delivery_city = '';

    public int $number_of_boxes = 1;

    public float $shipping_cost = 0;

    public string $cost_mode = 'per_invoice';

    public string $tracking_number = '';

    public string $waybill_number = '';

    public string $notes = '';

    // Deliver (POD)
    public bool $showDeliver = false;

    public ?int $deliverId = null;

    public $pod;

    // Return
    public bool $showReturn = false;

    public ?int $returnShipmentId = null;

    public ?int $return_product_id = null;

    public int $return_quantity = 1;

    public string $return_reason = '';

    // Detail
    public bool $showDetail = false;

    public ?int $detailId = null;

    /** @var list<array{id:int,name:string}> */
    public array $invoiceOptions = [];

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

        $this->invoiceOptions = app(FormSelectCatalog::class)->openSalesInvoicesForShipment();
        $this->formCatalogsLoaded = true;
    }

    public function mount(): void
    {
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->toDateString();
    }

    public function canManage(): bool
    {
        return Gate::allows('shipping.manage');
    }

    public function with(ShippingService $service): array
    {
        return [
            'shipments' => Shipment::query()
                ->search($this->search)
                ->status($this->statusFilter)
                ->with(['customer:id,name', 'logisticsCompany:id,name', 'invoice:id,invoice_number'])
                ->latest('id')
                ->paginate(10),
            'companyOptions' => LogisticsCompany::query()->active()->orderBy('name')->get(['id', 'name'])->all(),
            'statusOptions' => collect(array_keys(Shipment::TRANSITIONS))
                ->map(fn ($s) => ['id' => $s, 'name' => __('shipping.status.' . $s)])->all(),
            'modeOptions' => [
                ['id' => 'per_invoice', 'name' => __('shipping.mode.per_invoice')],
                ['id' => 'global', 'name' => __('shipping.mode.global')],
            ],
            'report' => $service->report($this->from, $this->to),
            'headers' => [
                ['key' => 'tracking', 'label' => __('shipping.tracking')],
                ['key' => 'customer', 'label' => __('nav.customers')],
                ['key' => 'route', 'label' => __('shipping.route')],
                ['key' => 'company', 'label' => __('shipping.company')],
                ['key' => 'status', 'label' => __('common.status')],
                ['key' => 'shipping_cost', 'label' => __('shipping.shipping_cost'), 'class' => 'text-end'],
            ],
        ];
    }

    public function money($amount): string
    {
        return Money::format($amount);
    }

    public function statusClass(string $status): string
    {
        return match ($status) {
            'delivered' => 'badge-success',
            'returned' => 'badge-error',
            'in_transit', 'handed_to_logistics' => 'badge-info',
            'arrived' => 'badge-primary',
            'processing' => 'badge-warning',
            default => 'badge-ghost',
        };
    }

    public function openCreate(): void
    {
        $this->loadFormCatalogs();
        $this->reset(['sales_invoice_id', 'logistics_company_id', 'dispatch_city', 'delivery_city', 'tracking_number', 'waybill_number', 'notes']);
        $this->number_of_boxes = 1;
        $this->shipping_cost = 0;
        $this->cost_mode = 'per_invoice';
        $this->showForm = true;
    }

    public function save(ShippingService $service): void
    {
        $this->authorize('shipping.manage');

        $data = $this->validate([
            'sales_invoice_id' => 'required|integer|exists:sales_invoices,id',
            'logistics_company_id' => 'required|integer|exists:logistics_companies,id',
            'dispatch_city' => 'required|string|max:100',
            'delivery_city' => 'required|string|max:100',
            'number_of_boxes' => 'integer|min:0',
            'shipping_cost' => 'numeric|min:0',
            'cost_mode' => 'required|in:per_invoice,global',
            'tracking_number' => 'nullable|string|max:100',
            'waybill_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $invoice = SalesInvoice::findOrFail($data['sales_invoice_id']);
            $service->createShipment($invoice, $data);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return;
        }

        $this->showForm = false;
        $this->success(__('shipping.created'));
    }

    public function advance(int $id, ShippingService $service): void
    {
        $this->authorize('shipping.manage');
        $shipment = Shipment::findOrFail($id);

        if ($shipment->nextStatus() === Shipment::STATUS_DELIVERED) {
            $this->deliverId = $id;
            $this->pod = null;
            $this->showDeliver = true;

            return;
        }

        try {
            $service->advance($shipment);
            $this->success(__('shipping.status_updated'));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }
    }

    public function confirmDeliver(ShippingService $service): void
    {
        $this->authorize('shipping.manage');
        $this->validate(['pod' => 'nullable|image|max:4096']);

        $shipment = Shipment::findOrFail($this->deliverId);
        $podPath = $this->pod ? $this->pod->store('pod', 'public') : null;

        try {
            $service->advance($shipment, Shipment::STATUS_DELIVERED, $podPath);
            $this->showDeliver = false;
            $this->success(__('shipping.delivered'));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }
    }

    public function openReturn(int $id): void
    {
        $this->returnShipmentId = $id;
        $this->return_product_id = null;
        $this->return_quantity = 1;
        $this->return_reason = '';
        $this->showReturn = true;
    }

    public function returnProductOptions(): array
    {
        if (! $this->returnShipmentId) {
            return [];
        }

        $shipment = Shipment::with('invoice.items.product:id,name')->find($this->returnShipmentId);

        return ($shipment?->invoice?->items ?? collect())
            ->map(fn ($item) => ['id' => $item->product_id, 'name' => $item->product?->name])
            ->unique('id')->values()->all();
    }

    public function saveReturn(ShippingService $service): void
    {
        $this->authorize('shipping.manage');

        $data = $this->validate([
            'return_product_id' => 'required|integer|exists:products,id',
            'return_quantity' => 'required|integer|min:1',
            'return_reason' => 'nullable|string|max:255',
        ]);

        $shipment = Shipment::findOrFail($this->returnShipmentId);

        try {
            $service->registerReturn($shipment, [
                'product_id' => $data['return_product_id'],
                'quantity' => $data['return_quantity'],
                'reason' => $data['return_reason'] ?: null,
            ]);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return;
        }

        $this->showReturn = false;
        $this->success(__('shipping.return_registered'));
    }

    public function processReturn(int $returnId, ShippingService $service): void
    {
        $this->authorize('shipping.manage');

        try {
            $service->processReturn(ShipmentReturn::findOrFail($returnId));
            $this->success(__('shipping.return_processed'));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }
    }

    public function rejectReturn(int $returnId, ShippingService $service): void
    {
        $this->authorize('shipping.manage');
        $service->rejectReturn(ShipmentReturn::findOrFail($returnId));
        $this->warning(__('shipping.return_rejected'));
    }

    public function openDetail(int $id): void
    {
        $this->detailId = $id;
        $this->showDetail = true;
    }

    public function detail(): ?Shipment
    {
        if (! $this->detailId) {
            return null;
        }

        return Shipment::query()
            ->with(['customer:id,name,phone', 'logisticsCompany:id,name', 'invoice:id,invoice_number', 'returns.product:id,name'])
            ->find($this->detailId);
    }
}; ?>

<div class="space-y-6">
    <x-ui.header :title="__('nav.shipping')" separator progress-indicator>
        <x-slot:actions>
            @if ($this->canManage())
                <x-ui.button :label="__('shipping.new')" icon="o-plus" class="btn-primary btn-sm" wire:click="openCreate" />
            @endif
        </x-slot:actions>
    </x-ui.header>

    {{-- Report --}}
    <x-ui.card :title="__('shipping.report')">
        <x-slot:menu>
            <x-ui.input wire:model.live="from" type="date" class="w-40" />
            <x-ui.input wire:model.live="to" type="date" class="w-40" />
        </x-slot:menu>
        <div class="grid gap-4 lg:grid-cols-4">
            <x-ui.stats-group compact class="lg:col-span-2">
                <x-ui.stats-row>
                    <x-slot:first>
                        <x-ui.stat :title="__('shipping.total_shipments')" :value="(string) $report['total']" icon="o-paper-airplane" />
                    </x-slot:first>
                    <x-slot:second>
                        <x-ui.stat :title="__('shipping.shipping_cost')" :value="$this->money($report['shipping_cost'])" icon="o-banknotes" color="text-error" />
                    </x-slot:second>
                </x-ui.stats-row>
            </x-ui.stats-group>
            <div class="lg:col-span-2">
                <div class="mb-2 text-sm font-medium">{{ __('shipping.by_status') }}</div>
                <div class="flex flex-wrap gap-1">
                    @forelse ($report['by_status'] as $status => $count)
                        <x-ui.badge :value="__('shipping.status.' . $status) . ': ' . $count" class="{{ $this->statusClass($status) }} badge-sm" />
                    @empty
                        <span class="text-sm text-base-content/50">{{ __('common.no_results') }}</span>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <div class="mb-1 text-sm font-medium">{{ __('shipping.top_cities') }}</div>
                @forelse ($report['top_cities'] as $c)
                    <div class="flex justify-between text-sm"><span>{{ $c['city'] }}</span><span class="text-base-content/50">{{ $c['count'] }}</span></div>
                @empty
                    <span class="text-sm text-base-content/50">—</span>
                @endforelse
            </div>
            <div>
                <div class="mb-1 text-sm font-medium">{{ __('shipping.top_companies') }}</div>
                @forelse ($report['top_companies'] as $c)
                    <div class="flex justify-between text-sm"><span>{{ $c['company'] }}</span><span class="text-base-content/50">{{ $c['count'] }}</span></div>
                @empty
                    <span class="text-sm text-base-content/50">—</span>
                @endforelse
            </div>
        </div>
    </x-ui.card>

    {{-- Table --}}
    <x-ui.card class="relative">
        <x-ui.table-loading />
        <x-ui.table :headers="$headers" :rows="$shipments" with-pagination>
            <x-slot:toolbar>
                <x-ui.table-filters>
                    <x-ui.input :placeholder="__('common.search')" wire:model.live.debounce.400ms="search" clearable icon="o-magnifying-glass" class="input-sm w-full sm:max-w-xs" />
                    <x-ui.select wire:model.live="statusFilter" :options="$statusOptions" option-value="id" option-label="name"
                        :placeholder="__('common.all')" class="select-sm w-full sm:w-48" />
                </x-ui.table-filters>
            </x-slot:toolbar>
            @scope('cell_tracking', $row)
                <div class="font-medium">{{ $row->tracking_number ?? '—' }}</div>
                <div class="text-xs text-base-content/50">{{ $row->invoice?->invoice_number }}</div>
            @endscope
            @scope('cell_customer', $row)
                {{ $row->customer?->name }}
            @endscope
            @scope('cell_route', $row)
                <span class="text-sm">{{ $row->dispatch_city }} → {{ $row->delivery_city }}</span>
            @endscope
            @scope('cell_company', $row)
                <span class="text-sm">{{ $row->logisticsCompany?->name }}</span>
            @endscope
            @scope('cell_status', $row)
                <x-ui.badge :value="__('shipping.status.' . $row->status)" class="{{ $this->statusClass($row->status) }}" />
            @endscope
            @scope('cell_shipping_cost', $row)
                <span class="text-end tabular-nums">{{ $this->money($row->shipping_cost) }}</span>
            @endscope
            @scope('actions', $row)
                <x-ui.button icon="o-eye" wire:click.stop="openDetail({{ $row->id }})" class="btn-text btn-circle btn-sm" />
                @if ($this->canManage() && ! $row->isFinal())
                    @if ($row->nextStatus())
                        <x-ui.button :label="__('shipping.status.' . $row->nextStatus())" icon="o-arrow-right"
                            wire:click.stop="advance({{ $row->id }})" class="btn-text btn-circle btn-sm text-primary" />
                    @endif
                    <x-ui.button icon="o-arrow-uturn-left" wire:click.stop="openReturn({{ $row->id }})"
                        class="btn-text btn-circle btn-sm text-error" tooltip="{{ __('shipping.return') }}" />
                @endif
            @endscope
        </x-ui.table>
    </x-ui.card>

    {{-- Create --}}
    <x-ui.modal wire:model="showForm" :title="__('shipping.new')" separator box-class="max-w-2xl">
        <div class="grid gap-4">
            <x-form.search-select :label="__('sales.invoice')" wire:model="sales_invoice_id" :options="$invoiceOptions"
                :placeholder="__('common.none')" />
            <x-form.search-select :label="__('shipping.company')" wire:model="logistics_company_id" :options="$companyOptions"
                :placeholder="__('common.none')" />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.input :label="__('shipping.dispatch_city')" wire:model="dispatch_city" />
                <x-ui.input :label="__('shipping.delivery_city')" wire:model="delivery_city" />
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <x-ui.input :label="__('shipping.boxes')" wire:model="number_of_boxes" type="number" min="0" />
                <x-ui.input :label="__('shipping.shipping_cost')" wire:model="shipping_cost" type="number" step="0.01" min="0" />
                <x-ui.select :label="__('shipping.cost_mode')" wire:model="cost_mode" :options="$modeOptions"
                    option-value="id" option-label="name" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.input :label="__('shipping.tracking')" wire:model="tracking_number" />
                <x-ui.input :label="__('shipping.waybill')" wire:model="waybill_number" />
            </div>
            <x-ui.textarea :label="__('fields.notes')" wire:model="notes" rows="2" />
        </div>
        <x-slot:actions>
            <x-ui.button :label="__('common.cancel')" wire:click="$set('showForm', false)" />
            <x-ui.button :label="__('common.save')" class="btn-primary" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-ui.modal>

    {{-- Deliver / POD --}}
    <x-ui.modal wire:model="showDeliver" :title="__('shipping.mark_delivered')" separator box-class="max-w-lg">
        <x-ui.file :label="__('shipping.pod')" wire:model="pod" accept="image/*" :hint="__('shipping.pod_hint')" />
        <x-slot:actions>
            <x-ui.button :label="__('common.cancel')" wire:click="$set('showDeliver', false)" />
            <x-ui.button :label="__('shipping.confirm_delivery')" class="btn-primary" wire:click="confirmDeliver" spinner="confirmDeliver" />
        </x-slot:actions>
    </x-ui.modal>

    {{-- Return --}}
    <x-ui.modal wire:model="showReturn" :title="__('shipping.return')" separator box-class="max-w-lg">
        <div class="grid gap-4">
            <x-form.search-select :label="__('nav.products')" wire:model="return_product_id" :options="$this->returnProductOptions()"
                :placeholder="__('common.none')" />
            <x-ui.input :label="__('inventory.quantity')" wire:model="return_quantity" type="number" min="1" />
            <x-ui.input :label="__('shipping.reason')" wire:model="return_reason" />
        </div>
        <x-slot:actions>
            <x-ui.button :label="__('common.cancel')" wire:click="$set('showReturn', false)" />
            <x-ui.button :label="__('shipping.register_return')" class="btn-primary" wire:click="saveReturn" spinner="saveReturn" />
        </x-slot:actions>
    </x-ui.modal>

    {{-- Detail drawer --}}
    <x-ui.drawer wire:model="showDetail" right separator with-close-button class="w-11/12 lg:w-2/5"
        :title="$this->detail()?->tracking_number ?? __('nav.shipping')" :subtitle="$this->detail()?->invoice?->invoice_number">
        @if ($s = $this->detail())
            <div class="space-y-4">
                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.badge :value="__('shipping.status.' . $s->status)" class="{{ $this->statusClass($s->status) }}" />
                    <x-ui.badge :value="__('shipping.mode.' . $s->cost_mode)" class="badge-ghost" />
                    <span class="ms-auto text-sm text-base-content/60">{{ $s->customer?->name }}</span>
                </div>

                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div><span class="text-base-content/50">{{ __('shipping.route') }}:</span> {{ $s->dispatch_city }} → {{ $s->delivery_city }}</div>
                    <div><span class="text-base-content/50">{{ __('shipping.company') }}:</span> {{ $s->logisticsCompany?->name }}</div>
                    <div><span class="text-base-content/50">{{ __('shipping.boxes') }}:</span> {{ $s->number_of_boxes }}</div>
                    <div><span class="text-base-content/50">{{ __('shipping.shipping_cost') }}:</span> {{ $this->money($s->shipping_cost) }}</div>
                    <div><span class="text-base-content/50">{{ __('shipping.waybill') }}:</span> {{ $s->waybill_number ?? '—' }}</div>
                    <div><span class="text-base-content/50">{{ __('shipping.value') }}:</span> {{ $this->money($s->shipment_value) }}</div>
                </div>

                @if ($s->pod_image)
                    <div>
                        <div class="mb-1 text-sm font-medium">{{ __('shipping.pod') }}</div>
                        <img src="{{ Storage::url($s->pod_image) }}" class="max-h-48 rounded-box border border-base-300" />
                    </div>
                @endif

                <div>
                    <div class="mb-2 text-sm font-medium">{{ __('shipping.returns') }}</div>
                    @forelse ($s->returns as $r)
                        <div class="mb-2 rounded-box border border-base-300 p-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-medium">{{ $r->product?->name }}</div>
                                    <div class="text-xs text-base-content/60">{{ __('inventory.quantity') }}: {{ $r->quantity }} · {{ $r->reason }}</div>
                                </div>
                                <x-ui.badge :value="__('shipping.return_status.' . $r->status)"
                                    class="{{ $r->status === 'processed' ? 'badge-success' : ($r->status === 'rejected' ? 'badge-error' : 'badge-warning') }} badge-sm" />
                            </div>
                            @if ($this->canManage() && in_array($r->status, ['pending', 'approved'], true))
                                <div class="mt-2 flex gap-2">
                                    <x-ui.button :label="__('shipping.process_return')" icon="o-check" wire:click="processReturn({{ $r->id }})" class="btn-text btn-circle btn-xs text-success" />
                                    <x-ui.button :label="__('common.reject')" icon="o-x-mark" wire:click="rejectReturn({{ $r->id }})" class="btn-text btn-circle btn-xs text-error" />
                                </div>
                            @endif
                        </div>
                    @empty
                        <span class="text-sm text-base-content/50">{{ __('common.no_results') }}</span>
                    @endforelse
                </div>
            </div>
        @endif
    </x-ui.drawer>
</div>
