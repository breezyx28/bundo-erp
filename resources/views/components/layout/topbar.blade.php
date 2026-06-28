@props(['title' => null])

@php($user = auth()->user())
@php($locale = app()->getLocale())
@php($otherLocale = $locale === 'ar' ? 'en' : 'ar')
@php($platformMode = app(\App\Services\Tenancy\TenantContext::class)->isPlatformMode())

<div class="navbar flex w-full min-w-0 items-center gap-2 overflow-hidden p-0 lg:gap-4">
    {{-- Page title --}}
    <div class="navbar-start min-w-0">
        <h1 class="truncate text-base font-semibold tracking-tight text-base-content">{{ $title ?? config('app.name') }}</h1>
    </div>

    {{-- Global search --}}
    <div class="navbar-center hidden max-w-md flex-1 md:flex">
        <label class="input input-sm w-full max-w-md border-base-content/10 bg-base-200/40 shadow-sm">
            <x-ui.icon name="o-magnifying-glass" class="size-4 shrink-0 opacity-50" />
            <input type="search" class="grow bg-transparent text-sm" placeholder="{{ __('common.search') }}" />
        </label>
    </div>

    <div class="navbar-end ms-auto flex shrink-0 items-center gap-2 lg:gap-2.5">
        @unless ($platformMode)
            <livewire:layout.branch-selector />
        @endunless

        {{-- Locale switcher with flags --}}
        <div class="join rounded-[var(--radius-field)] border border-base-content/10 bg-base-100 p-0.5 shadow-sm">
            <a
                href="{{ route('locale.switch', 'ar') }}"
                @class([
                    'btn btn-sm join-item min-h-8 gap-1.5 px-2.5 text-xs font-medium',
                    'btn-primary' => $locale === 'ar',
                    'btn-text' => $locale !== 'ar',
                ])
                aria-label="العربية"
            >
                <span class="text-base leading-none" aria-hidden="true">🇸🇦</span>
                <span>AR</span>
            </a>
            <a
                href="{{ route('locale.switch', 'en') }}"
                @class([
                    'btn btn-sm join-item min-h-8 gap-1.5 px-2.5 text-xs font-medium',
                    'btn-primary' => $locale === 'en',
                    'btn-text' => $locale !== 'en',
                ])
                aria-label="English"
            >
                <span class="text-base leading-none" aria-hidden="true">🇺🇸</span>
                <span>EN</span>
            </a>
        </div>

        <livewire:layout.notification-bell />

        <x-ui.dropdown right>
            <x-slot:trigger>
                <button type="button" class="dropdown-toggle btn btn-text btn-circle min-h-9 min-w-9 p-0" aria-label="{{ __('auth.my_profile') }}">
                    <x-ui.avatar :initials="$user?->initials() ?? 'G'" size="md" />
                </button>
            </x-slot:trigger>

            <li class="pointer-events-none px-4 py-2">
                <p class="text-sm font-semibold">{{ $user?->name }}</p>
                <p class="text-xs text-base-content/60">{{ $user?->email }}</p>
            </li>
            <x-ui.menu-separator />
            <x-ui.menu-item :title="__('auth.my_profile')" icon="o-user" link="#" />
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="dropdown-item text-error">
                    <x-ui.icon name="o-arrow-right-on-rectangle" class="size-4" />
                    {{ __('auth.sign_out') }}
                </button>
            </form>
        </x-ui.dropdown>
    </div>
</div>
