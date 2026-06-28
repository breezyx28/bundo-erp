<?php

use App\Models\Branch;
use App\Models\User;
use App\Services\Branch\BranchContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\UiToast;
use Spatie\Permission\Models\Role;

new #[Layout('components.layouts.app')] #[Title('Users')] class extends Component
{
    use UiToast, WithPagination;

    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $role = 'salesperson';

    /** @var array<int> */
    public array $branchIds = [];

    public bool $is_active = true;

    public function mount(): void
    {
        $this->authorize('users.manage');
    }

    public function with(): array
    {
        return [
            'users' => User::query()
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate(10),
            'branches' => Branch::query()->orderBy('name')->get(),
            'roles' => Role::query()->whereNotIn('name', ['super_admin'])->orderBy('name')->pluck('name')->all(),
            'headers' => [
                ['key' => 'name', 'label' => __('fields.name')],
                ['key' => 'email', 'label' => __('auth.email')],
                ['key' => 'is_active', 'label' => __('common.status')],
            ],
        ];
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'email', 'phone', 'password', 'password_confirmation']);
        $this->role = 'salesperson';
        $this->branchIds = [app(BranchContext::class)->currentBranchId() ?? 0];
        $this->is_active = true;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $user = User::findOrFail($id);
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = (string) $user->phone;
        $this->is_active = (bool) $user->is_active;
        $this->role = $user->roles->first()?->name ?? 'salesperson';
        $this->branchIds = $user->branches()->pluck('branches.id')->all();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize('users.manage');

        $tenantId = app(BranchContext::class)->currentTenantId();

        $rules = [
            'name' => 'required|string|max:120',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->editingId)],
            'phone' => 'nullable|string|max:30',
            'role' => ['required', Rule::in(Role::query()->whereNotIn('name', ['super_admin'])->pluck('name')->all())],
            'branchIds' => 'required|array|min:1',
            'branchIds.*' => 'integer|exists:branches,id',
            'is_active' => 'boolean',
        ];

        if ($this->editingId) {
            $rules['password'] = 'nullable|string|min:8|confirmed';
        } else {
            $rules['password'] = 'required|string|min:8|confirmed';
        }

        $data = $this->validate($rules);

        if ($this->editingId && $this->editingId === Auth::id() && ! $data['is_active']) {
            $this->error(__('users.cannot_deactivate_self'));

            return;
        }

        $payload = [
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'is_active' => $data['is_active'],
            'default_branch_id' => $data['branchIds'][0] ?? null,
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $user->update($payload);
        } else {
            $user = User::create($payload);
        }

        $user->syncRoles([$data['role']]);
        $user->branches()->sync(collect($data['branchIds'])->mapWithKeys(fn ($id) => [$id => ['is_primary' => $id === ($data['branchIds'][0] ?? null)]])->all());

        $this->success(__('users.saved'));
        $this->showModal = false;
    }
}; ?>

<div class="space-y-6">
    <x-ui.header :title="__('nav.users')" separator>
        <x-slot:actions>
            <x-ui.button :label="__('users.add')" wire:click="create" class="btn-primary btn-sm" icon="o-plus" />
        </x-slot:actions>
    </x-ui.header>

    <x-ui.card class="relative overflow-hidden">
        <x-ui.table-loading />
        <x-ui.table :headers="$headers" :rows="$users" striped with-pagination>
            <x-slot:toolbar>
                <x-ui.table-filters>
                    <x-ui.input wire:model.live.debounce.400ms="search" icon="o-magnifying-glass" :placeholder="__('common.search')" class="input-sm w-full sm:max-w-xs" />
                </x-ui.table-filters>
            </x-slot:toolbar>
            @scope('cell_is_active', $user)
                <x-ui.badge :value="$user->is_active ? __('common.active') : __('common.inactive')" :class="$user->is_active ? 'badge-success' : 'badge-ghost'" />
            @endscope
            @scope('actions', $user)
                <x-ui.button icon="o-pencil-square" wire:click.stop="edit({{ $user->id }})" class="btn-text btn-circle btn-sm" />
            @endscope
        </x-ui.table>
    </x-ui.card>

    <x-ui.modal wire:model="showModal" :title="$editingId ? __('users.edit') : __('users.add')" class="backdrop-blur">
        <div class="grid gap-4 md:grid-cols-2">
            <x-ui.input :label="__('fields.name')" wire:model="name" />
            <x-ui.input :label="__('auth.email')" wire:model="email" type="email" />
            <x-ui.input :label="__('fields.phone')" wire:model="phone" />
            <x-ui.select :label="__('users.role')" wire:model="role" :options="collect($roles)->map(fn ($r) => ['id' => $r, 'name' => $r])->all()" />
            <x-ui.password :label="__('users.password')" wire:model="password" />
            <x-ui.password :label="__('users.password_confirm')" wire:model="password_confirmation" />
            <div class="md:col-span-2">
                <p class="mb-2 text-sm font-medium">{{ __('users.branches') }}</p>
                <div class="flex flex-wrap gap-3">
                    @foreach ($branches as $branch)
                        <x-ui.checkbox :label="$branch->name" wire:model="branchIds" :value="$branch->id" />
                    @endforeach
                </div>
            </div>
            <x-ui.checkbox :label="__('common.active')" wire:model="is_active" />
        </div>
        <x-slot:actions>
            <x-ui.button :label="__('common.cancel')" wire:click="$set('showModal', false)" />
            <x-ui.button :label="__('common.save')" wire:click="save" class="btn-primary" spinner="save" />
        </x-slot:actions>
    </x-ui.modal>
</div>
