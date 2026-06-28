@php($menu = app(\App\Support\Navigation::class)->menu())
@php($brand = app(\App\Support\TenantBranding::class))
@php($tenantContext = app(\App\Services\Tenancy\TenantContext::class))
@php($viewingTenant = $tenantContext->isSuperAdmin() && ! $tenantContext->isPlatformMode() ? $tenantContext->currentTenant() : null)

<div class="flex h-full min-h-0 flex-col bg-base-100">
    {{-- Brand header --}}
    <div class="flex h-14 shrink-0 items-center gap-2.5 border-b border-base-content/10 px-3 lg:h-[72px] lg:gap-3 lg:px-4">
        @if ($logo = $brand->logoUrl())
            <img src="{{ $logo }}" alt="" class="size-8 rounded-[var(--radius-field)] object-cover lg:size-9" />
        @else
            <x-ui.avatar :initials="mb_substr($brand->companyName(), 0, 1)" size="sm" />
        @endif
        <span class="truncate text-sm font-semibold tracking-tight lg:text-base">{{ $brand->companyName() }}</span>
    </div>

    @if ($viewingTenant)
        <div class="shrink-0 border-b border-base-content/10 bg-warning/10 px-3 py-2 text-[10px] lg:px-4 lg:py-3 lg:text-xs">
            <p class="font-medium">{{ __('platform.viewing_as', ['name' => $viewingTenant->name]) }}</p>
            <form method="POST" action="{{ route('platform.exit-tenant') }}" class="mt-1.5 lg:mt-2">
                @csrf
                <button type="submit" class="link link-primary text-[10px] lg:text-xs">{{ __('platform.exit_tenant') }}</button>
            </form>
        </div>
    @endif

    {{-- Primary navigation (FlyonUI menu-sm) --}}
    <nav
        class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-2"
        aria-label="{{ __('nav.dashboard') }}"
        data-sidebar-nav
    >
        <ul class="menu menu-sm w-full gap-0.5 p-0">
            @foreach ($menu as $item)
                <li>
                    <a
                        href="{{ $item['route'] ? route($item['route']) : '#' }}"
                        @if ($item['route']) wire:navigate wire:current="menu-active" @endif
                    >
                        <x-ui.icon :name="$item['icon']" class="size-4.5 shrink-0 opacity-80" />
                        <span class="grow truncate">{{ __($item['label']) }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    {{-- Footer --}}
    <div class="shrink-0 border-t border-base-content/10 p-2 text-[10px] text-base-content/50">
        {{ $brand->companyName() }} · v0.1
    </div>
</div>
