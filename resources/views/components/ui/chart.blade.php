@props([
    'title' => null,
])

<div {{ $attributes->class(['min-h-72 w-full']) }}>
    {{ $slot }}
</div>
