{{-- Scoped overlay: only blocks table during filter/search refresh, not action clicks --}}
<div
    wire:loading.delay.flex
    wire:target="search, categoryFilter, brandFilter, statusFilter, branchFilter, typeFilter, filter, from, to"
    class="pointer-events-none absolute inset-0 z-10 hidden items-center justify-center rounded-[var(--radius-box)] bg-base-100/60 backdrop-blur-[2px]"
>
    <span class="loading loading-spinner loading-md text-primary"></span>
</div>
