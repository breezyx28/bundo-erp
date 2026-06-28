<?php

use App\Models\StockTransfer;
use App\Services\Branch\BranchContext;
use App\Services\Inventory\StockTransferService;
use App\Support\FormSelectCatalog;
use App\Traits\ConfirmsDeletion;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\UiToast;

new #[Layout('components.layouts.app')] #[Title('Stock Transfers')] class extends Component
{
    use ConfirmsDeletion, UiToast, WithPagination;

    public string $statusFilter = '';

    // Create modal
    public bool $showCreate = false;

    public ?int $from_branch_id = null;

    public ?int $to_branch_id = null;

    public string $notes = '';

    /** @var array<int, array{product_id:?int, quantity:int}> */
    public array $items = [];

    // Detail drawer
    public bool $showDetail = false;

    public ?int $detailId = null;

    /** @var list<array{id:int,name:string}> */
    public array $productOptions = [];

    public bool $formCatalogsLoaded = false;

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    protected function loadFormCatalogs(): void
    {
        if ($this->formCatalogsLoaded) {
            return;
        }

        $this->productOptions = app(FormSelectCatalog::class)->products();
        $this->formCatalogsLoaded = true;
    }

    protected function context(): BranchContext
    {
        return app(BranchContext::class);
    }

    public function canManage(): bool
    {
        return Gate::allows('inventory.transfer');
    }

    public function with(): array
    {
        return [
            'transfers' => StockTransfer::query()
                ->visible()
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->with(['fromBranch:id,name', 'toBranch:id,name'])
                ->latest('id')
                ->paginate(10),
            'branchOptions' => $this->context()->allowedBranches()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])->all(),
            'statusOptions' => collect(['requested', 'approved', 'dispatched', 'received', 'cancelled'])
                ->map(fn ($s) => ['id' => $s, 'name' => __('inventory.status.' . $s)])->all(),
            'headers' => [
                ['key' => 'number', 'label' => __('inventory.transfer_no')],
                ['key' => 'from', 'label' => __('inventory.from_branch')],
                ['key' => 'to', 'label' => __('inventory.to_branch')],
                ['key' => 'status', 'label' => __('common.status')],
                ['key' => 'created', 'label' => __('inventory.requested_at')],
            ],
        ];
    }

    public function openCreate(): void
    {
        $this->loadFormCatalogs();
        $this->reset(['to_branch_id', 'notes']);
        $this->from_branch_id = $this->context()->currentBranchId() ?? ($this->context()->allowedBranchIds()->first());
        $this->items = [['product_id' => null, 'quantity' => 1]];
        $this->showCreate = true;
    }

    public function addItem(): void
    {
        $this->items[] = ['product_id' => null, 'quantity' => 1];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function create(StockTransferService $service): void
    {
        $this->authorize('inventory.transfer');

        $data = $this->validate([
            'from_branch_id' => 'required|integer|different:to_branch_id',
            'to_branch_id' => 'required|integer',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $service->request(
            fromBranchId: $data['from_branch_id'],
            toBranchId: $data['to_branch_id'],
            items: $data['items'],
            notes: $data['notes'] ?: null,
        );

        $this->showCreate = false;
        $this->success(__('inventory.transfer_created'));
    }

    public function applyTransferAction(string $action, int $id, StockTransferService $service): void
    {
        $this->authorize('inventory.transfer');

        $transfer = StockTransfer::query()->visible()->findOrFail($id);

        try {
            match ($action) {
                'approve' => $service->approve($transfer),
                'dispatch' => $service->dispatch($transfer),
                'receive' => $service->receive($transfer),
                'cancel' => $service->cancel($transfer),
                default => throw new \InvalidArgumentException('Unknown action.'),
            };

            $this->success(__('inventory.transfer_' . $action . 'd'));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }
    }

    public function openDetail(int $id): void
    {
        $this->detailId = $id;
        $this->showDetail = true;
    }

    public function detail(): ?StockTransfer
    {
        if (! $this->detailId) {
            return null;
        }

        return StockTransfer::query()->visible()
            ->with(['items.product:id,name', 'items.variant', 'fromBranch:id,name', 'toBranch:id,name'])
            ->find($this->detailId);
    }

    public function cancelConfirmed(StockTransferService $service): void
    {
        if ($this->deleteId === null) {
            return;
        }

        $this->applyTransferAction('cancel', $this->deleteId, $service);
        $this->cancelDelete();
    }

    public function statusClass(string $status): string
    {
        return match ($status) {
            'requested' => 'badge-info',
            'approved' => 'badge-primary',
            'dispatched' => 'badge-warning',
            'received' => 'badge-success',
            'cancelled' => 'badge-ghost',
            default => 'badge-ghost',
        };
    }
}; ?>

<div>
    <x-ui.header :title="__('inventory.stock_transfers')" separator progress-indicator>
        <x-slot:actions>
            @if ($this->canManage())
                <x-ui.button :label="__('inventory.new_transfer')" icon="o-plus" class="btn-primary btn-sm" wire:click="openCreate" />
            @endif
        </x-slot:actions>
    </x-ui.header>

    <x-ui.card class="relative">
        <x-ui.table-loading />
        <x-ui.table :headers="$headers" :rows="$transfers" with-pagination>
            <x-slot:toolbar>
                <x-ui.table-filters>
                    <x-ui.select wire:model.live="statusFilter" :options="$statusOptions" option-value="id" option-label="name"
                        :placeholder="__('common.all')" class="select-sm w-full sm:w-48" />
                </x-ui.table-filters>
            </x-slot:toolbar>
            @scope('cell_from', $row)
                {{ $row->fromBranch?->name }}
            @endscope
            @scope('cell_to', $row)
                {{ $row->toBranch?->name }}
            @endscope
            @scope('cell_status', $row)
                <x-ui.badge :value="__('inventory.status.' . $row->status)" class="{{ $this->statusClass($row->status) }}" />
            @endscope
            @scope('cell_created', $row)
                <span class="text-xs text-base-content/60">{{ $row->created_at->format('Y-m-d') }}</span>
            @endscope
            @scope('actions', $row)
                <div class="flex gap-1">
                    <x-ui.button icon="o-eye" wire:click="openDetail({{ $row->id }})" class="btn-text btn-circle btn-sm" />
                    @if ($this->canManage())
                        @if ($row->status === 'requested')
                            <x-ui.button icon="o-check" wire:click="applyTransferAction('approve', {{ $row->id }})"
                                class="btn-text btn-circle btn-sm text-primary" tooltip="{{ __('inventory.approve') }}" />
                        @elseif ($row->status === 'approved')
                            <x-ui.button icon="o-paper-airplane" wire:click="applyTransferAction('dispatch', {{ $row->id }})"
                                class="btn-ghost btn-sm text-warning" tooltip="{{ __('inventory.dispatch') }}" />
                        @elseif ($row->status === 'dispatched')
                            <x-ui.button icon="o-arrow-down-on-square" wire:click="applyTransferAction('receive', {{ $row->id }})"
                                class="btn-text btn-circle btn-sm text-success" tooltip="{{ __('inventory.receive') }}" />
                        @endif
                        @if (in_array($row->status, ['requested', 'approved'], true))
                            <x-ui.button icon="o-x-mark" wire:click.stop="confirmDelete({{ $row->id }})"
                                class="btn-text btn-circle btn-sm text-error"
                                tooltip="{{ __('inventory.cancel') }}" />
                        @endif
                    @endif
                </div>
            @endscope
        </x-ui.table>
    </x-ui.card>

    {{-- Create transfer --}}
    <x-ui.modal wire:model="showCreate" :title="__('inventory.new_transfer')" separator box-class="max-w-2xl">
        <div class="grid gap-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.select :label="__('inventory.from_branch')" wire:model="from_branch_id" :options="$branchOptions"
                    option-value="id" option-label="name" :placeholder="__('common.none')" />
                <x-ui.select :label="__('inventory.to_branch')" wire:model="to_branch_id" :options="$branchOptions"
                    option-value="id" option-label="name" :placeholder="__('common.none')" />
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium">{{ __('inventory.items') }}</span>
                    <x-ui.button :label="__('inventory.add_item')" icon="o-plus" class="btn-text btn-circle btn-xs" wire:click="addItem" />
                </div>
                @foreach ($items as $i => $item)
                    <div class="flex items-end gap-2" wire:key="item-{{ $i }}">
                        <x-form.search-select wire:model="items.{{ $i }}.product_id" :options="$productOptions"
                            :placeholder="__('nav.products')" class="flex-1" />
                        <x-ui.input wire:model="items.{{ $i }}.quantity" type="number" min="1" class="w-28"
                            :placeholder="__('inventory.quantity')" />
                        <x-ui.button icon="o-trash" wire:click="removeItem({{ $i }})" class="btn-text btn-circle btn-sm text-error" />
                    </div>
                @endforeach
                @error('items') <span class="text-xs text-error">{{ $message }}</span> @enderror
            </div>

            <x-ui.textarea :label="__('fields.notes')" wire:model="notes" rows="2" />
        </div>
        <x-slot:actions>
            <x-ui.button :label="__('common.cancel')" wire:click="$set('showCreate', false)" />
            <x-ui.button :label="__('common.create')" class="btn-primary" wire:click="create" spinner="create" />
        </x-slot:actions>
    </x-ui.modal>

    {{-- Detail drawer --}}
    <x-ui.drawer wire:model="showDetail" right separator with-close-button class="w-11/12 lg:w-1/3"
        :title="$this->detail()?->number" :subtitle="__('inventory.stock_transfers')">
        @if ($t = $this->detail())
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <x-ui.badge :value="__('inventory.status.' . $t->status)" class="{{ $this->statusClass($t->status) }}" />
                    <span class="text-sm text-base-content/60">{{ $t->fromBranch?->name }} → {{ $t->toBranch?->name }}</span>
                </div>

                <div class="divide-y divide-base-300 rounded-box border border-base-300">
                    @foreach ($t->items as $item)
                        <div class="flex items-center justify-between p-3">
                            <div>
                                <div class="font-medium">{{ $item->product?->name }}</div>
                                @if ($item->variant)
                                    <div class="text-xs text-base-content/50">{{ $item->variant->label() }}</div>
                                @endif
                            </div>
                            <div class="text-end tabular-nums">
                                <div class="font-semibold">{{ number_format($item->quantity) }}</div>
                                @if ($item->received_quantity)
                                    <div class="text-xs text-success">{{ __('inventory.received') }}: {{ number_format($item->received_quantity) }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($t->notes)
                    <x-ui.alert :title="$t->notes" icon="o-chat-bubble-bottom-center-text" class="alert-soft" />
                @endif
            </div>
        @endif
    </x-ui.drawer>

    <x-ui.delete-confirm-modal :message="__('common.confirm') . '?'" confirm-action="cancelConfirmed" :confirm-label="__('inventory.cancel')" />
</div>
