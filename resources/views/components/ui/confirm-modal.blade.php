@props([
    'title' => null,
    'message' => null,
    'confirmLabel' => null,
    'cancelLabel' => null,
    'confirmClass' => 'btn-error',
])

<x-ui.modal
    {{ $attributes->whereStartsWith('wire:model') }}
    :title="$title ?? __('common.confirm_delete')"
    separator
    box-class="max-w-md"
>
    <p class="text-sm text-base-content/80">{{ $message ?? __('common.confirm_delete') }}</p>

    <x-slot:actions>
        <x-ui.button
            :label="$cancelLabel ?? __('common.cancel')"
            wire:click="{{ $attributes->wire('model')->value() }} = false"
            class="btn-text btn-sm"
        />
        {{ $actions ?? '' }}
    </x-slot:actions>
</x-ui.modal>
