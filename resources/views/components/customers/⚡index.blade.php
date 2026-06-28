<?php

use App\Models\Customer;
use App\Traits\ConfirmsDeletion;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\UiToast;

new #[Layout('components.layouts.app')] #[Title('Customers')] class extends Component
{
    use ConfirmsDeletion, UiToast, WithPagination;

    public string $search = '';

    public string $typeFilter = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $address = '';

    public string $type = 'retail';

    public float $credit_limit = 0;

    public float $opening_balance = 0;

    public string $notes = '';

    public bool $is_active = true;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'customers' => Customer::query()
                ->with('branchBalances')
                ->search($this->search)
                ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
                ->orderBy('name')
                ->paginate(10),
            'headers' => [
                ['key' => 'name', 'label' => __('fields.name')],
                ['key' => 'phone', 'label' => __('fields.phone')],
                ['key' => 'type', 'label' => __('fields.type')],
                ['key' => 'balance', 'label' => __('fields.balance'), 'sortable' => false],
                ['key' => 'badges', 'label' => __('fields.badges'), 'sortable' => false],
            ],
            'typeOptions' => [
                ['id' => 'retail', 'name' => __('fields.retail')],
                ['id' => 'wholesale', 'name' => __('fields.wholesale')],
            ],
        ];
    }

    public function canManage(): bool
    {
        return Gate::allows('customers.create');
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'phone', 'email', 'address', 'notes']);
        $this->type = 'retail';
        $this->credit_limit = 0;
        $this->opening_balance = 0;
        $this->is_active = true;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $c = Customer::findOrFail($id);
        $this->editingId = $c->id;
        $this->fill($c->only(['name', 'phone', 'email', 'address', 'type', 'is_active']));
        $this->credit_limit = (float) $c->credit_limit;
        $this->opening_balance = (float) $c->opening_balance;
        $this->notes = (string) $c->notes;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'customers.update' : 'customers.create');

        $data = $this->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'type' => 'required|in:retail,wholesale',
            'credit_limit' => 'numeric|min:0',
            'opening_balance' => 'numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        Customer::updateOrCreate(['id' => $this->editingId], $data);

        $this->showModal = false;
        $this->success($this->editingId ? __('common.updated') : __('common.created'));
    }

    public function deleteConfirmed(): void
    {
        if ($this->deleteId === null) {
            return;
        }

        $this->authorize('customers.delete');
        Customer::findOrFail($this->deleteId)->delete();
        $this->cancelDelete();
        $this->warning(__('common.deleted'));
    }
}; ?>

<div>
    <x-ui.header :title="__('nav.customers')" separator progress-indicator>
        <x-slot:actions>
            @if ($this->canManage())
                <x-ui.button :label="__('common.create')" icon="o-plus" class="btn-primary btn-sm" wire:click="create" />
            @endif
        </x-slot:actions>
    </x-ui.header>

    <x-ui.card class="relative">
        <x-ui.table-loading />
        <x-ui.table :headers="$headers" :rows="$customers" with-pagination>
            <x-slot:toolbar>
                <x-ui.table-filters>
                    <x-ui.select wire:model.live="typeFilter" :options="$typeOptions" :placeholder="__('common.all')"
                        placeholder-value="" class="select-sm w-full sm:w-40" />
                    <x-ui.input :placeholder="__('common.search')" wire:model.live.debounce.400ms="search" clearable icon="o-magnifying-glass" class="input-sm w-full sm:max-w-xs" />
                </x-ui.table-filters>
            </x-slot:toolbar>
            @scope('cell_type', $row)
                <x-ui.badge :value="__('fields.' . $row->type)" class="badge-ghost" />
            @endscope
            @scope('cell_balance', $row)
                <span class="tabular-nums font-medium">{{ \App\Support\Money::format($row->currentBalance()) }}</span>
            @endscope
            @scope('cell_badges', $row)
                <div class="flex flex-wrap gap-1">
                    @foreach ($row->badges() as $badge)
                        <x-ui.badge :value="__('badges.' . $badge['label'])" class="{{ $badge['color'] }} badge-sm" />
                    @endforeach
                </div>
            @endscope
            @scope('actions', $row)
                @if ($this->canManage())
                    <x-ui.button icon="o-pencil" wire:click.stop="edit({{ $row->id }})" class="btn-text btn-circle btn-sm" />
                    <x-ui.button icon="o-trash" wire:click.stop="confirmDelete({{ $row->id }})" class="btn-text btn-circle btn-sm text-error" />
                @endif
            @endscope
        </x-ui.table>
    </x-ui.card>

    <x-ui.modal wire:model="showModal" :title="$editingId ? __('common.edit') : __('common.create')" separator box-class="max-w-2xl">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.input :label="__('fields.name')" wire:model="name" />
            <x-ui.input :label="__('fields.phone')" wire:model="phone" />
            <x-ui.input :label="__('fields.email')" wire:model="email" />
            <x-ui.select :label="__('fields.type')" wire:model="type" :options="$typeOptions" />
            <x-ui.input :label="__('fields.credit_limit')" wire:model="credit_limit" type="number" step="0.01" />
            <x-ui.input :label="__('fields.opening_balance')" wire:model="opening_balance" type="number" step="0.01" />
            <div class="sm:col-span-2">
                <x-ui.textarea :label="__('fields.address')" wire:model="address" rows="2" />
            </div>
            <div class="sm:col-span-2">
                <x-ui.textarea :label="__('fields.notes')" wire:model="notes" rows="2" />
            </div>
            <x-ui.toggle :label="__('common.active')" wire:model="is_active" />
        </div>
        <x-slot:actions>
            <x-ui.button :label="__('common.cancel')" wire:click="$set('showModal', false)" />
            <x-ui.button :label="__('common.save')" class="btn-primary" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-ui.modal>

    <x-ui.delete-confirm-modal />
</div>
