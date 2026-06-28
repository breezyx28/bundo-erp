<?php

use App\Models\Supplier;
use App\Traits\ConfirmsDeletion;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\UiToast;

new #[Layout('components.layouts.app')] #[Title('Suppliers')] class extends Component
{
    use ConfirmsDeletion, UiToast, WithPagination;

    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $contact_person = '';

    public string $phone = '';

    public string $email = '';

    public string $address = '';

    public string $tax_number = '';

    public float $opening_balance = 0;

    public string $notes = '';

    public bool $is_active = true;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'suppliers' => Supplier::query()
                ->search($this->search)
                ->orderBy('name')
                ->paginate(10),
            'headers' => [
                ['key' => 'name', 'label' => __('fields.name')],
                ['key' => 'contact_person', 'label' => __('fields.contact_person')],
                ['key' => 'phone', 'label' => __('fields.phone')],
                ['key' => 'is_active', 'label' => __('common.status')],
            ],
        ];
    }

    public function canManage(): bool
    {
        return Gate::allows('suppliers.create');
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'contact_person', 'phone', 'email', 'address', 'tax_number', 'notes']);
        $this->opening_balance = 0;
        $this->is_active = true;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $s = Supplier::findOrFail($id);
        $this->editingId = $s->id;
        $this->fill($s->only(['name', 'contact_person', 'phone', 'email', 'address', 'tax_number', 'is_active']));
        $this->opening_balance = (float) $s->opening_balance;
        $this->notes = (string) $s->notes;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'suppliers.update' : 'suppliers.create');

        $data = $this->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'tax_number' => 'nullable|string|max:100',
            'opening_balance' => 'numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        Supplier::updateOrCreate(['id' => $this->editingId], $data);

        $this->showModal = false;
        $this->success($this->editingId ? __('common.updated') : __('common.created'));
    }

    public function deleteConfirmed(): void
    {
        if ($this->deleteId === null) {
            return;
        }

        $this->authorize('suppliers.delete');
        Supplier::findOrFail($this->deleteId)->delete();
        $this->cancelDelete();
        $this->warning(__('common.deleted'));
    }
}; ?>

<div>
    <x-ui.header :title="__('nav.suppliers')" separator progress-indicator>
        <x-slot:actions>
            @if ($this->canManage())
                <x-ui.button :label="__('common.create')" icon="o-plus" class="btn-primary btn-sm" wire:click="create" />
            @endif
        </x-slot:actions>
    </x-ui.header>

    <x-ui.card class="relative">
        <x-ui.table-loading />
        <x-ui.table :headers="$headers" :rows="$suppliers" with-pagination>
            <x-slot:toolbar>
                <x-ui.table-filters>
                    <x-ui.input :placeholder="__('common.search')" wire:model.live.debounce.400ms="search" clearable icon="o-magnifying-glass" class="input-sm w-full sm:max-w-xs" />
                </x-ui.table-filters>
            </x-slot:toolbar>
            @scope('cell_is_active', $row)
                <x-ui.badge :value="$row->is_active ? __('common.active') : __('common.inactive')"
                    class="{{ $row->is_active ? 'badge-success' : 'badge-ghost' }}" />
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
            <x-ui.input :label="__('fields.contact_person')" wire:model="contact_person" />
            <x-ui.input :label="__('fields.phone')" wire:model="phone" />
            <x-ui.input :label="__('fields.email')" wire:model="email" />
            <x-ui.input :label="__('fields.tax_number')" wire:model="tax_number" />
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
