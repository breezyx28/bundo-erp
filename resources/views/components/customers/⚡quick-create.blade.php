<?php

use App\Models\Customer;
use Livewire\Component;
use App\Traits\UiToast;

/**
 * Reusable "Quick Customer" modal. Open it from any component by dispatching
 * `open-quick-customer`. On save it emits `customer-created` with the new id,
 * so parent screens (e.g. the sales invoice) can select it immediately.
 */
new class extends Component
{
    use UiToast;

    public bool $show = false;

    public string $name = '';

    public string $phone = '';

    public string $type = 'retail';

    protected $listeners = ['open-quick-customer' => 'open'];

    public function open(): void
    {
        $this->reset(['name', 'phone']);
        $this->type = 'retail';
        $this->show = true;
    }

    public function save(): void
    {
        $this->authorize('customers.create');

        $data = $this->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'type' => 'required|in:retail,wholesale',
        ]);

        $customer = Customer::create($data);

        $this->show = false;
        $this->success(__('common.created'));
        $this->dispatch('customer-created', id: $customer->id, name: $customer->name);
    }
}; ?>

<div>
    <x-ui.modal wire:model="show" :title="__('customers.quick_add')" separator>
        <div class="space-y-4">
            <x-ui.input :label="__('fields.name')" wire:model="name" autofocus />
            <x-ui.input :label="__('fields.phone')" wire:model="phone" />
            <x-ui.select :label="__('fields.type')" wire:model="type" :options="[
                ['id' => 'retail', 'name' => __('fields.retail')],
                ['id' => 'wholesale', 'name' => __('fields.wholesale')],
            ]" />
        </div>
        <x-slot:actions>
            <x-ui.button :label="__('common.cancel')" wire:click="$set('show', false)" />
            <x-ui.button :label="__('common.save')" class="btn-primary" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-ui.modal>
</div>
