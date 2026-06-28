<?php

use App\Models\ExpenseCategory;
use App\Traits\ConfirmsDeletion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\UiToast;

new #[Layout('components.layouts.app')] #[Title('Expense Categories')] class extends Component
{
    use ConfirmsDeletion, UiToast, WithPagination;

    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public bool $is_operational = true;

    public bool $is_active = true;

    public function canManage(): bool
    {
        return Gate::allows('expenses.update');
    }

    public function with(): array
    {
        return [
            'categories' => ExpenseCategory::query()
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->withCount('expenses')
                ->orderBy('name')
                ->paginate(10),
            'headers' => [
                ['key' => 'name', 'label' => __('fields.name')],
                ['key' => 'is_operational', 'label' => __('expenses.operational')],
                ['key' => 'expenses_count', 'label' => __('nav.expenses'), 'class' => 'text-end'],
                ['key' => 'is_active', 'label' => __('common.status')],
            ],
        ];
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'description']);
        $this->is_operational = true;
        $this->is_active = true;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $c = ExpenseCategory::findOrFail($id);
        $this->editingId = $c->id;
        $this->fill($c->only(['name', 'is_operational', 'is_active']));
        $this->description = (string) $c->description;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize('expenses.update');

        $data = $this->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_operational' => 'boolean',
            'is_active' => 'boolean',
        ]);

        ExpenseCategory::updateOrCreate(
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

        $this->authorize('expenses.delete');
        ExpenseCategory::findOrFail($this->deleteId)->delete();
        $this->cancelDelete();
        $this->warning(__('common.deleted'));
    }
}; ?>

<div>
    <x-ui.header :title="__('nav.expense_categories')" separator progress-indicator>
        <x-slot:actions>
            @if ($this->canManage())
                <x-ui.button :label="__('common.create')" icon="o-plus" class="btn-primary btn-sm" wire:click="create" />
            @endif
        </x-slot:actions>
    </x-ui.header>

    <x-ui.card class="relative">
        <x-ui.table-loading />
        <x-ui.table :headers="$headers" :rows="$categories" with-pagination>
            <x-slot:toolbar>
                <x-ui.table-filters>
                    <x-ui.input :placeholder="__('common.search')" wire:model.live.debounce.400ms="search" clearable icon="o-magnifying-glass" class="input-sm w-full sm:max-w-xs" />
                </x-ui.table-filters>
            </x-slot:toolbar>
            @scope('cell_is_operational', $row)
                <x-ui.badge :value="$row->is_operational ? __('expenses.operational') : __('expenses.non_operational')"
                    class="{{ $row->is_operational ? 'badge-info' : 'badge-ghost' }} badge-sm" />
            @endscope
            @scope('cell_expenses_count', $row)
                <span class="text-end tabular-nums text-base-content/60">{{ number_format($row->expenses_count) }}</span>
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

    <x-ui.modal wire:model="showModal" :title="$editingId ? __('common.edit') : __('common.create')" separator box-class="max-w-lg">
        <div class="grid gap-4">
            <x-ui.input :label="__('fields.name')" wire:model="name" />
            <x-ui.textarea :label="__('fields.description')" wire:model="description" rows="2" />
            <x-ui.toggle :label="__('expenses.operational')" wire:model="is_operational" />
            <x-ui.toggle :label="__('common.active')" wire:model="is_active" />
        </div>
        <x-slot:actions>
            <x-ui.button :label="__('common.cancel')" wire:click="$set('showModal', false)" />
            <x-ui.button :label="__('common.save')" class="btn-primary" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-ui.modal>

    <x-ui.delete-confirm-modal />
</div>
