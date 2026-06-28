@props([
    'toolbar' => null,
])

<div {{ $attributes->class(['w-full']) }}>
    @if ($toolbar)
        <div class="flex flex-col flex-wrap gap-3 sm:flex-row sm:items-center sm:justify-between">
            {{ $toolbar }}
        </div>
    @endif

    <div @class(['overflow-x-auto', 'mt-8' => (bool) $toolbar])>
        {{ $slot }}
    </div>
</div>
