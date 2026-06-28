@props([
    'options' => [],
    'optionValue' => 'id',
    'optionLabel' => 'name',
    'label' => null,
    'placeholder' => null,
    'hint' => null,
    'height' => 'max-h-60',
])

<x-ui.choices-offline
    :label="$label"
    :hint="$hint"
    :options="$options"
    :option-value="$optionValue"
    :option-label="$optionLabel"
    :placeholder="$placeholder ?? __('common.search')"
    :height="$height"
    single
    searchable
    clearable
    {{ $attributes }}
/>
