import { reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { route as ziggyRoute } from 'ziggy-js';

/**
 * Reactive server-side table filters. Any change debounces an Inertia GET to
 * the given route, preserving scroll/state (mirrors wire:model.live.debounce).
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
                router.get(
                    ziggyRoute(routeName),
                    { ...filters },
                    {
                        preserveState: true,
                        preserveScroll: true,
                        replace: true,
                    },
                );
            }, debounce);
        },
        { deep: true },
    );

    return { filters };
}
