@php
if (! isset($scrollTo)) {
    $scrollTo = false;
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';

$isRtl = in_array(app()->getLocale(), ['ar', 'fa', 'he', 'ur'], true);
@endphp

@if ($paginator->total() > 0)
    <div class="flex flex-wrap items-center justify-between gap-3 py-4 pt-6">
        <div class="text-sm text-base-content/70">
            @if ($paginator->firstItem())
                {{ __('common.showing') }}
                <span class="font-semibold text-base-content">{{ $paginator->firstItem() }}-{{ $paginator->lastItem() }}</span>
                {{ __('common.of') }}
                <span class="font-semibold text-base-content">{{ $paginator->total() }}</span>
                {{ __('common.results') }}
            @else
                <span class="font-semibold text-base-content">{{ $paginator->count() }}</span>
                {{ __('common.results') }}
            @endif
        </div>

        @if ($paginator->hasPages())
            <nav class="join rounded-[var(--radius-field)] border border-base-content/10 bg-base-100 p-0.5 shadow-sm" role="navigation" aria-label="{{ __('common.pagination_navigation') }}">
                @if ($paginator->onFirstPage())
                    <button type="button" class="btn btn-text btn-sm join-item min-h-9 min-w-9 px-2" aria-label="{{ __('pagination.previous') }}" disabled>
                        @if ($isRtl)
                            <span class="icon-[tabler--chevron-right] size-4"></span>
                        @else
                            <span class="icon-[tabler--chevron-left] size-4"></span>
                        @endif
                    </button>
                @else
                    <button
                        type="button"
                        wire:click="previousPage('{{ $paginator->getPageName() }}')"
                        @if ($scrollIntoViewJsSnippet) x-on:click="{{ $scrollIntoViewJsSnippet }}" @endif
                        wire:loading.attr="disabled"
                        class="btn btn-text btn-sm join-item min-h-9 min-w-9 px-2"
                        aria-label="{{ __('pagination.previous') }}"
                    >
                        @if ($isRtl)
                            <span class="icon-[tabler--chevron-right] size-4"></span>
                        @else
                            <span class="icon-[tabler--chevron-left] size-4"></span>
                        @endif
                    </button>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <button type="button" class="btn btn-text btn-sm join-item min-h-9 min-w-9 px-2" disabled>{{ $element }}</button>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <button type="button" class="btn btn-primary btn-sm join-item min-h-9 min-w-9 px-2" aria-current="page">{{ $page }}</button>
                            @else
                                <button
                                    type="button"
                                    wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                    @if ($scrollIntoViewJsSnippet) x-on:click="{{ $scrollIntoViewJsSnippet }}" @endif
                                    class="btn btn-text btn-sm join-item min-h-9 min-w-9 px-2"
                                >
                                    {{ $page }}
                                </button>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <button
                        type="button"
                        wire:click="nextPage('{{ $paginator->getPageName() }}')"
                        @if ($scrollIntoViewJsSnippet) x-on:click="{{ $scrollIntoViewJsSnippet }}" @endif
                        wire:loading.attr="disabled"
                        class="btn btn-text btn-sm join-item min-h-9 min-w-9 px-2"
                        aria-label="{{ __('pagination.next') }}"
                    >
                        @if ($isRtl)
                            <span class="icon-[tabler--chevron-left] size-4"></span>
                        @else
                            <span class="icon-[tabler--chevron-right] size-4"></span>
                        @endif
                    </button>
                @else
                    <button type="button" class="btn btn-text btn-sm join-item min-h-9 min-w-9 px-2" aria-label="{{ __('pagination.next') }}" disabled>
                        @if ($isRtl)
                            <span class="icon-[tabler--chevron-left] size-4"></span>
                        @else
                            <span class="icon-[tabler--chevron-right] size-4"></span>
                        @endif
                    </button>
                @endif
            </nav>
        @endif
    </div>
@endif
