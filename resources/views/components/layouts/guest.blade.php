@props(['title' => null])

@php($isRtl = app()->getLocale() === 'ar')
<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ $isRtl ? 'rtl' : 'ltr' }}"
    data-theme="mazin"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title . ' · ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-base-200 font-sans text-base-content antialiased">
    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="w-full max-w-md">
            <div class="mb-8 flex flex-col items-center gap-3">
                <div class="flex size-14 items-center justify-center rounded-sm bg-primary text-2xl font-bold text-primary-content">M</div>
                <h1 class="text-2xl font-bold tracking-tight">{{ config('app.name') }}</h1>
            </div>

            <div class="card bg-base-100 p-8">
                {{ $slot }}
            </div>
        </div>
    </div>

    <x-ui.toast />
</body>
</html>
