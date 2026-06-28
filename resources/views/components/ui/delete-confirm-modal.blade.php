@props([
    'message' => null,
    'confirmLabel' => null,
    'confirmAction' => 'deleteConfirmed',
])

<x-ui.modal wire:model="confirmDelete" :title="__('common.confirm_delete')" separator box-class="max-w-md">
    <p class="text-sm text-base-content/80">{{ $message ?? __('common.confirm_delete') }}</p>
    <x-slot:actions>
        <x-ui.button :label="__('common.cancel')" class="btn-text btn-sm" wire:click="cancelDelete" />
        <x-ui.button
            :label="$confirmLabel ?? __('common.delete')"
            icon="o-trash"
            class="btn-error btn-sm"
            wire:click="{{ $confirmAction }}"
            spinner
        />
    </x-slot:actions>
</x-ui.modal>
