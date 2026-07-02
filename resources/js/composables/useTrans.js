import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Client-side mirror of Laravel's __() helper.
 *
 * Reads the `translations` object shared by HandleInertiaRequests, which is a
 * nested map of `{ group: { key: value, nested: { key: value } } }` for the
 * active locale. Supports dot-notation keys and `:param` replacement, matching
 * the Blade usage such as __('debts.bucket.current') and
 * __('platform.viewing_as', ['name' => ...]).
 */
export function useTrans() {
    const page = usePage();

    const translations = computed(() => page.props.translations || {});

    function t(key, replace = {}) {
        const resolved = String(key)
            .split('.')
            .reduce(
                (acc, part) =>
                    acc && typeof acc === 'object' ? acc[part] : undefined,
                translations.value,
            );

        let str = typeof resolved === 'string' ? resolved : key;

        for (const [param, value] of Object.entries(replace)) {
            str = str.replace(new RegExp(`:${param}`, 'g'), value);
        }

        return str;
    }

    return { t };
}
