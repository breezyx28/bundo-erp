@props(['class' => ''])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-end gap-2 ' . $class]) }}>
    {{ $slot }}
</div>
