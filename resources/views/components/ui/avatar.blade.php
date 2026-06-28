@props([
    'initials' => '?',
    'size' => 'md',
])

@php($sizes = [
    'sm' => 'size-8 text-xs',
    'md' => 'size-9.5 text-sm',
    'lg' => 'size-11 text-base',
])

<div {{ $attributes->class(['avatar avatar-placeholder']) }}>
    <div @class([$sizes[$size] ?? $sizes['md'], 'bg-primary/10 font-semibold text-primary rounded-field'])>
        {{ $initials }}
    </div>
</div>
