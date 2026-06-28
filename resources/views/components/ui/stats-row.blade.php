@props([
    'single' => false,
])

<div {{ $attributes->class(['flex flex-1 gap-4', 'max-sm:flex-col' => ! $single]) }}>
    @if ($single)
        {{ $slot }}
    @elseif (isset($first) || isset($second))
        @isset($first)
            {{ $first }}
        @endisset
        @if (isset($first) && isset($second))
            <div class="divider sm:divider-horizontal"></div>
        @endif
        @isset($second)
            {{ $second }}
        @endisset
    @else
        {{ $slot }}
    @endif
</div>
