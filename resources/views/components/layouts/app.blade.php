@props(['title' => null])

@php($isRtl = app()->getLocale() === 'ar')
@php($brand = app(\App\Support\TenantBranding::class))
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

    <link rel="icon" href="/favicon.ico" sizes="any">

    {{-- PWA shell --}}
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="{{ $brand->primaryColor() }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [data-theme="mazin"] {
            --color-primary: {{ $brand->primaryColor() }};
            --color-secondary: {{ $brand->secondaryColor() }};
        }
    </style>
</head>
<body class="min-h-screen overflow-x-clip bg-base-200 font-sans text-base-content antialiased">
    <div class="flex min-h-screen overflow-x-clip">
        {{-- Sidebar: persisted across wire:navigate so scroll position is kept --}}
        @persist('app-sidebar')
            <aside class="hidden w-52 shrink-0 border-e border-base-content/10 bg-base-100 lg:sticky lg:top-0 lg:flex lg:h-svh lg:max-h-svh lg:flex-col lg:overflow-hidden">
                <x-layout.sidebar />
            </aside>
        @endpersist

        {{-- Main column --}}
        <div class="flex min-w-0 flex-1 flex-col">
            {{-- Top navigation --}}
            <header class="sticky top-0 z-30 flex h-14 shrink-0 items-center border-b border-base-content/10 bg-base-100/95 px-4 backdrop-blur sm:px-6 lg:h-[72px]">
                <x-layout.topbar :title="$title" />
            </header>

            {{-- Page content --}}
            <main id="main-content" tabindex="-1" class="flex-1 overflow-x-clip p-4 focus:outline-none sm:p-6">
                <div class="mx-auto w-full max-w-[1320px] min-w-0">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    {{-- FlyonUI toast host --}}
    <x-ui.toast />

    {{-- PWA: register the offline shell service worker --}}
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            });
        }
    </script>
</body>
</html>
