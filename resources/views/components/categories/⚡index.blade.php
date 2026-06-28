<?php

use App\Models\Category;
use App\Traits\ConfirmsDeletion;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\UiToast;

new #[Layout('components.layouts.app')] #[Title('Categories')] class extends Component
{
    use ConfirmsDeletion, UiToast, WithPagination;

    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public ?int $parent_id = null;

    public string $description = '';

    public bool $is_active = true;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'categories' => Category::query()
                ->with('parent')
                ->withCount('products')
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate(10),
            'headers' => [
                ['key' => 'name', 'label' => __('fields.name')],
                ['key' => 'parent', 'label' => __('fields.parent')],
                ['key' => 'products_count', 'label' => __('nav.products')],
                ['key' => 'is_active', 'label' => __('common.status')],
            ],
            'parents' => Category::query()->roots()->orderBy('name')->get(),
        ];
    }

    public function parentOptions(): Collection
    {
        return Category::query()->roots()
            ->when($this->editingId, fn ($q) => $q->whereKeyNot($this->editingId))
            ->orderBy('name')->get(['id', 'name']);
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'parent_id', 'description']);
        $this->is_active = true;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $category = Category::findOrFail($id);
        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->parent_id = $category->parent_id;
        $this->description = (string) $category->description;
        $this->is_active = $category->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        Category::updateOrCreate(['id' => $this->editingId], $data);

        $this->showModal = false;
        $this->success($this->editingId ? __('common.updated') : __('common.created'));
    }

    public function deleteConfirmed(): void
    {
        if ($this->deleteId === null) {
            return;
        }

        Category::findOrFail($this->deleteId)->delete();
        $this->cancelDelete();
        $this->warning(__('common.deleted'));
    }
}; ?>

<div>
    <x-ui.header :title="__('nav.categories')" separator progress-indicator>
        <x-slot:actions>
            <x-ui.button :label="__('common.create')" icon="o-plus" class="btn-primary btn-sm" wire:click="create" />
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
            @scope('cell_parent', $row)
                {{ $row->parent?->name ?? '—' }}
            @endscope
            @scope('cell_is_active', $row)
                <x-ui.badge :value="$row->is_active ? __('common.active') : __('common.inactive')"
                    class="{{ $row->is_active ? 'badge-success' : 'badge-ghost' }}" />
            @endscope
            @scope('actions', $row)
                <x-ui.button icon="o-pencil" wire:click.stop="edit({{ $row->id }})" class="btn-text btn-circle btn-sm" />
                <x-ui.button icon="o-trash" wire:click.stop="confirmDelete({{ $row->id }})" class="btn-text btn-circle btn-sm text-error" />
            @endscope
        </x-ui.table>
    </x-ui.card>

    <x-ui.modal wire:model="showModal" :title="$editingId ? __('common.edit') : __('common.create')" separator>
        <div class="space-y-4">
            <x-ui.input :label="__('fields.name')" wire:model="name" />
            <x-form.search-select :label="__('fields.parent')" wire:model="parent_id"
                :options="$this->parentOptions()->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->all()"
                placeholder="—" />
            <x-ui.textarea :label="__('fields.description')" wire:model="description" rows="2" />
            <x-ui.toggle :label="__('common.active')" wire:model="is_active" />
        </div>
        <x-slot:actions>
            <x-ui.button :label="__('common.cancel')" wire:click="$set('showModal', false)" />
            <x-ui.button :label="__('common.save')" class="btn-primary" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-ui.modal>

    <x-ui.delete-confirm-modal />
</div>
