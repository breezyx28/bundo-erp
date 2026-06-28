@props([
    'name',
])

@php($iconClass = \App\Support\TablerIcons::resolve($name))

<span {{ $attributes->class([$iconClass, 'inline-block shrink-0']) }} aria-hidden="true"></span>
