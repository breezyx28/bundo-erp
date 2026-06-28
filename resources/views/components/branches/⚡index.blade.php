<?php

use App\Models\Branch;
use App\Models\StockLocation;
use App\Services\Branch\BranchContext;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\UiToast;

new #[Layout('components.layouts.app')] #[Title('Branches')] class extends Component
{
    use UiToast, WithPagination;

    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $code = '';

    public string $address = '';

    public string $phone = '';

    public string $email = '';

    public string $primary_color = '#39C6A0';

    public string $secondary_color = '#228C70';

    public bool $is_active = true;

    public function mount(): void
    {
        $this->authorize('branches.manage');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'branches' => Branch::query()
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate(10),
            'headers' => [
                ['key' => 'name', 'label' => __('fields.name')],
                ['key' => 'code', 'label' => __('branches.code')],
                ['key' => 'phone', 'label' => __('branches.phone')],
                ['key' => 'is_active', 'label' => __('common.status')],
            ],
        ];
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'code', 'address', 'phone', 'email']);
        $this->primary_color = '#39C6A0';
        $this->secondary_color = '#228C70';
        $this->is_active = true;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $branch = Branch::findOrFail($id);
        $this->editingId = $branch->id;
        $this->fill($branch->only(['name', 'code', 'address', 'phone', 'email', 'primary_color', 'secondary_color', 'is_active']));
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize('branches.manage');

        $tenantId = app(BranchContext::class)->currentTenantId();

        $data = $this->validate([
            'name' => 'required|string|max:120',
            'code' => ['required', 'string', 'max:10', Rule::unique('branches', 'code')->where('tenant_id', $tenantId)->ignore($this->editingId)],
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:120',
            'primary_color' => 'required|string|max:7',
            'secondary_color' => 'required|string|max:7',
            'is_active' => 'boolean',
        ]);

        if ($this->editingId) {
            $branch = Branch::findOrFail($this->editingId);

            if (! $data['is_active'] && Branch::where('tenant_id', $tenantId)->where('is_active', true)->count() <= 1) {
                $this->error(__('branches.cannot_delete_last'));

                return;
            }

            $branch->update($data);
        } else {
            $branch = Branch::create($data + ['tenant_id' => $tenantId]);

            StockLocation::create([
                'branch_id' => $branch->id,
                'name' => 'Main Store',
                'code' => 'MAIN',
                'type' => 'store',
                'is_default' => true,
                'is_active' => true,
            ]);
        }

        $this->success(__('branches.saved'));
        $this->showModal = false;
    }
}; ?>

<div class="space-y-6">
    <x-ui.header :title="__('nav.branches')" separator progress-indicator>
        <x-slot:actions>
            <x-ui.button :label="__('branches.add')" wire:click="create" class="btn-primary btn-sm" icon="o-plus" />
        </x-slot:actions>
    </x-ui.header>

    <x-ui.card class="relative overflow-hidden">
        <x-ui.table-loading />
        <x-ui.table :headers="$headers" :rows="$branches" striped with-pagination>
            <x-slot:toolbar>
                <x-ui.table-filters>
                    <x-ui.input wire:model.live.debounce.400ms="search" icon="o-magnifying-glass" :placeholder="__('common.search')" class="input-sm w-full sm:max-w-xs" />
                </x-ui.table-filters>
            </x-slot:toolbar>
            @scope('cell_is_active', $branch)
                <x-ui.badge :value="$branch->is_active ? __('common.active') : __('common.inactive')" :class="$branch->is_active ? 'badge-success' : 'badge-ghost'" />
            @endscope
            @scope('actions', $branch)
                <x-ui.button icon="o-pencil-square" wire:click.stop="edit({{ $branch->id }})" class="btn-circle btn-text btn-sm" />
            @endscope
        </x-ui.table>
    </x-ui.card>

    <x-ui.modal wire:model="showModal" :title="$editingId ? __('branches.edit') : __('branches.add')" class="backdrop-blur">
        <div class="grid gap-4 md:grid-cols-2">
            <x-ui.input :label="__('fields.name')" wire:model="name" />
            <x-ui.input :label="__('branches.code')" wire:model="code" />
            <x-ui.input :label="__('branches.phone')" wire:model="phone" />
            <x-ui.input :label="__('branches.email')" wire:model="email" type="email" />
            <x-ui.textarea :label="__('branches.address')" wire:model="address" class="md:col-span-2" />
            <x-ui.input type="color" :label="__('settings.primary_color')" wire:model="primary_color" />
            <x-ui.input type="color" :label="__('settings.secondary_color')" wire:model="secondary_color" />
            <x-ui.checkbox :label="__('common.active')" wire:model="is_active" />
        </div>
        <x-slot:actions>
            <x-ui.button :label="__('common.cancel')" wire:click="$set('showModal', false)" />
            <x-ui.button :label="__('common.save')" wire:click="save" class="btn-primary" spinner="save" />
        </x-slot:actions>
    </x-ui.modal>
</div>
