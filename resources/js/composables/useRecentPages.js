import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const MAX_STORED = 12;

/**
 * Tracks recently visited nav routes in localStorage (per user), so the
 * Links page can surface a "Recently used" row.
 */
export function useRecentPages() {
    const page = usePage();

    const storageKey = computed(
        () => `recent-pages:${page.props.auth?.user?.id ?? 'guest'}`,
    );

    const routes = ref(load());

    function load() {
        try {
            const raw = localStorage.getItem(storageKey.value);
            const parsed = raw ? JSON.parse(raw) : [];
            return Array.isArray(parsed) ? parsed.filter((r) => typeof r === 'string') : [];
        } catch {
            return [];
        }
    }

    function record(routeName) {
        if (!routeName) {
            return;
        }
        routes.value = [routeName, ...routes.value.filter((r) => r !== routeName)].slice(0, MAX_STORED);
        try {
            localStorage.setItem(storageKey.value, JSON.stringify(routes.value));
        } catch {
            // localStorage unavailable — recency is a convenience only.
        }
    }

    /** Map stored route names onto current nav items (drops stale/hidden pages). */
    function recentNavItems(nav, limit = 6) {
        const byRoute = new Map((nav ?? []).map((item) => [item.route, item]));
        return routes.value
            .map((r) => byRoute.get(r))
            .filter(Boolean)
            .slice(0, limit);
    }

    return { routes, record, recentNavItems };
}
