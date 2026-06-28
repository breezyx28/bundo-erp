@props(['title' => null])

<div class="animate-pulse space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div class="space-y-2">
            <div class="h-7 w-48 rounded-lg bg-base-300"></div>
            @if ($title)
                <div class="h-4 w-32 rounded bg-base-300"></div>
            @endif
        </div>
        <div class="h-10 w-28 rounded-xl bg-base-300"></div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach (range(1, 4) as $i)
            <div class="h-24 rounded-2xl bg-base-300/70"></div>
        @endforeach
    </div>

    <div class="rounded-2xl border border-base-300 bg-base-100 p-4">
        <div class="mb-4 h-5 w-40 rounded bg-base-300"></div>
        <div class="space-y-3">
            @foreach (range(1, 6) as $i)
                <div class="h-10 rounded-lg bg-base-200"></div>
            @endforeach
        </div>
    </div>
</div>
