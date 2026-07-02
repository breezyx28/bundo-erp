import { usePage } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';
import { ar, en } from '@nuxt/ui/locale';

/**
 * Resolves the active locale/direction from shared props and keeps the
 * document <html dir/lang> in sync (defensive for SPA navigations; the Blade
 * root view already sets them on the initial load). Also maps to the Nuxt UI
 * locale object which drives RTL for Nuxt UI components via UApp :locale.
 */
export function useDirection() {
    const page = usePage();

    const locale = computed(() => page.props.locale?.current || 'en');
    const dir = computed(
        () => page.props.locale?.dir || (locale.value === 'ar' ? 'rtl' : 'ltr'),
    );
    const nuxtLocale = computed(() => (locale.value === 'ar' ? ar : en));

    watchEffect(() => {
        if (typeof document !== 'undefined') {
            document.documentElement.dir = dir.value;
            document.documentElement.lang = locale.value;
        }
    });

    return { locale, dir, nuxtLocale };
}
