<?php

use App\Models\LogisticsCompany;
use App\Traits\ConfirmsDeletion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\UiToast;

new #[Layout('components.layouts.app')] #[Title('Logistics Companies')] class extends Component
{
    use ConfirmsDeletion, UiToast, WithPagination;

    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $contact_person = '';

    public string $address = '';

    public int $rating = 0;

    public string $notes = '';

    public bool $is_active = true;

    public function canManage(): bool
    {
        return Gate::allows('shipping.manage');
    }

    public function with(): array
    {
        return [
            'companies' => LogisticsCompany::query()
                ->search($this->search)
                ->withCount('shipments')
                ->orderBy('name')
                ->paginate(10),
            'headers' => [
                ['key' => 'name', 'label' => __('fields.name')],
                ['key' => 'contact_person', 'label' => __('shipping.contact_person')],
                ['key' => 'rating', 'label' => __('shipping.rating')],
                ['key' => 'shipments_count', 'label' => __('nav.shipping'), 'class' => 'text-end'],
                ['key' => 'is_active', 'label' => __('common.status')],
            ],
        ];
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'phone', 'email', 'contact_person', 'address', 'notes']);
        $this->rating = 0;
        $this->is_active = true;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $c = LogisticsCompany::findOrFail($id);
        $this->editingId = $c->id;
        $this->fill($c->only(['name', 'rating', 'is_active']));
        $this->phone = (string) $c->phone;
        $this->email = (string) $c->email;
        $this->contact_person = (string) $c->contact_person;
        $this->address = (string) $c->address;
        $this->notes = (string) $c->notes;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize('shipping.manage');

        $data = $this->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'contact_person' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'rating' => 'integer|min:0|max:5',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        LogisticsCompany::updateOrCreate(
            ['id' => $this->editingId],
            $data + ['tenant_id' => Auth::user()->tenant_id],
        );

        $this->showModal = false;
        $this->success($this->editingId ? __('common.updated') : __('common.created'));
    }

    public function deleteConfirmed(): void
    {
        if ($this->deleteId === null) {
            return;
        }

        $this->authorize('shipping.manage');
        LogisticsCompany::findOrFail($this->deleteId)->delete();
        $this->cancelDelete();
        $this->warning(__('common.deleted'));
    }
}; ?>

<div>
    <x-ui.header :title="__('nav.logistics')" separator progress-indicator>
        <x-slot:actions>
            @if ($this->canManage())
                <x-ui.button :label="__('common.create')" icon="o-plus" class="btn-primary btn-sm" wire:click="create" />
            @endif
        </x-slot:actions>
    </x-ui.header>

    <x-ui.card class="relative">
        <x-ui.table-loading />
        <x-ui.table :headers="$headers" :rows="$companies" with-pagination>
            <x-slot:toolbar>
                <x-ui.table-filters>
                    <x-ui.input :placeholder="__('common.search')" wire:model.live.debounce.400ms="search" clearable icon="o-magnifying-glass" class="input-sm w-full sm:max-w-xs" />
                </x-ui.table-filters>
            </x-slot:toolbar>
            @scope('cell_rating', $row)
                <div class="flex text-warning">
                    @for ($i = 1; $i <= 5; $i++)
                        <x-ui.icon :name="$i <= $row->rating ? 'o-star' : 'o-star'" class="{{ $i <= $row->rating ? 'text-warning' : 'text-base-content/20' }} w-4 h-4" />
                    @endfor
                </div>
            @endscope
            @scope('cell_shipments_count', $row)
                <span class="text-end tabular-nums text-base-content/60">{{ number_format($row->shipments_count) }}</span>
            @endscope
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

    <x-ui.modal wire:model="showModal" :title="$editingId ? __('common.edit') : __('common.create')" separator box-class="max-w-xl">
        <div class="grid gap-4">
            <x-ui.input :label="__('fields.name')" wire:model="name" />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.input :label="__('fields.phone')" wire:model="phone" />
                <x-ui.input :label="__('fields.email')" wire:model="email" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.input :label="__('shipping.contact_person')" wire:model="contact_person" />
                <x-ui.input :label="__('shipping.rating')" wire:model="rating" type="number" min="0" max="5" />
            </div>
            <x-ui.textarea :label="__('fields.address')" wire:model="address" rows="2" />
            <x-ui.textarea :label="__('fields.notes')" wire:model="notes" rows="2" />
            <x-ui.toggle :label="__('common.active')" wire:model="is_active" />
        </div>
        <x-slot:actions>
            <x-ui.button :label="__('common.cancel')" wire:click="$set('showModal', false)" />
            <x-ui.button :label="__('common.save')" class="btn-primary" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-ui.modal>

    <x-ui.delete-confirm-modal />
</div>
