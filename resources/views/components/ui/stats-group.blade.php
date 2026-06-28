@props([
    'compact' => false,
])

<div {{ $attributes->class([
    'flex w-full gap-4 max-xl:flex-col',
    'rounded-box bg-base-100 p-6' => ! $compact,
]) }}>
    {{ $slot }}
</div>
