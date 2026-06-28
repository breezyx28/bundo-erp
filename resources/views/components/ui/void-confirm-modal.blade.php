@props([
    'message' => null,
    'confirmLabel' => null,
    'confirmAction' => 'voidConfirmed',
])

<x-ui.modal wire:model="confirmVoid" :title="__('sales.void')" separator box-class="max-w-md">
    <p class="text-sm text-base-content/80">{{ $message ?? __('sales.confirm_void') }}</p>
    <x-slot:actions>
        <x-ui.button :label="__('common.cancel')" class="btn-text btn-sm" wire:click="cancelVoid" />
        <x-ui.button
            :label="$confirmLabel ?? __('sales.void')"
            icon="o-trash"
            class="btn-error btn-sm"
            wire:click="{{ $confirmAction }}"
            spinner
        />
    </x-slot:actions>
</x-ui.modal>
