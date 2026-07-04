import { reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { route as ziggyRoute } from 'ziggy-js';

/** Drop empty/null values so the query string (and URL) stays clean. */
function clean(source) {
    const out = {};
    for (const [key, value] of Object.entries(source)) {
        if (value === '' || value === null || value === undefined || value === false) {
            continue;
        }
        out[key] = value;
    }
    return out;
}

/**
 * Reactive server-side table filters. Any change debounces an Inertia GET to
 * the given route, preserving scroll/state (mirrors wire:model.live.debounce).
 *
 * Supports advanced params (sort, direction, date_from, date_to) transparently —
 * any key set on the returned `filters` object is forwarded to the server.
 */
export function useTableFilters(routeName, initial = {}, options = {}) {
    const filters = reactive({ ...initial });
    const debounce = options.debounce ?? 350;
    let timer = null;

    watch(
        filters,
        () => {
            clearTimeout(timer);
            timer = setTimeout(() => {
                router.get(ziggyRoute(routeName), clean(filters), {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                });
            }, debounce);
        },
        { deep: true },
    );

    // Toggle a sortable column: same column flips direction, new column resets to asc.
    function toggleSort(column) {
        if (filters.sort === column) {
            filters.direction = filters.direction === 'asc' ? 'desc' : 'asc';
        } else {
            filters.sort = column;
            filters.direction = 'asc';
        }
    }

    return { filters, toggleSort };
}
