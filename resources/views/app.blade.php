@php($isRtl = app()->getLocale() === 'ar')
@php($brand = app(\App\Support\TenantBranding::class))
<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ $isRtl ? 'rtl' : 'ltr' }}"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title inertia>{{ config('app.name') }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">

    {{-- PWA shell --}}
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="{{ $brand->primaryColor() }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">

    {{-- Per-tenant branding: override Nuxt UI accent tokens at runtime --}}
    <style>
        :root {
            --ui-primary: {{ $brand->primaryColor() }};
            --ui-secondary: {{ $brand->secondaryColor() }};
        }
    </style>

    @routes
    @vite(['resources/js/inertia.js'])
    @inertiaHead
</head>
<body class="min-h-screen bg-default text-default antialiased">
    <div class="isolate">
        @inertia
    </div>

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
