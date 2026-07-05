import { router, usePage } from '@inertiajs/vue3';
import { watch } from 'vue';

const HIGH_CONTRAST_BODY = '#000000';
const HIGH_CONTRAST_MUTED = '#1f2937';

const SCALE_MAP = {
    sm: 'sm',
    md: 'md',
    lg: 'lg',
    xl: 'xl',
};

function normalizeScale(scale) {
    return SCALE_MAP[scale] ?? 'md';
}

export function applyDisplayPreferences(prefs) {
    const root = document.documentElement;
    const scale = normalizeScale(prefs?.scale ?? 'md');
    const highContrast = Boolean(prefs?.highContrast);

    root.dataset.uiScale = scale;

    if (highContrast) {
        root.dataset.highContrast = 'true';
        root.dataset.customText = 'true';
        root.style.setProperty('--display-text-body', HIGH_CONTRAST_BODY);
        root.style.setProperty('--display-text-muted', HIGH_CONTRAST_MUTED);
        return;
    }

    delete root.dataset.highContrast;

    const body = prefs?.textBody || null;
    const muted = prefs?.textMuted || null;

    if (body || muted) {
        root.dataset.customText = 'true';
        if (body) {
            root.style.setProperty('--display-text-body', body);
        } else {
            root.style.removeProperty('--display-text-body');
        }
        if (muted) {
            root.style.setProperty('--display-text-muted', muted);
        } else {
            root.style.removeProperty('--display-text-muted');
        }
    } else {
        delete root.dataset.customText;
        root.style.removeProperty('--display-text-body');
        root.style.removeProperty('--display-text-muted');
    }
}

export function useDisplayPreferences() {
    const page = usePage();

    function syncFromPage() {
        applyDisplayPreferences(page.props.displayPrefs);
    }

    return { syncFromPage };
}

let bootstrapped = false;

/** Call once from inertia.js to apply prefs on load and navigation. */
export function bootstrapDisplayPreferences() {
    if (bootstrapped) {
        return;
    }
    bootstrapped = true;

    const sync = (page) => {
        applyDisplayPreferences(page?.props?.displayPrefs);
    };

    router.on('success', (event) => {
        sync(event.detail?.page);
    });

    router.on('navigate', (event) => {
        sync(event.detail?.page);
    });
}

/** Keep `<html>` scale/colors in sync anywhere AppLayout is mounted. */
export function useDisplayPreferencesSync() {
    const page = usePage();

    watch(
        () => page.props.displayPrefs,
        (prefs) => applyDisplayPreferences(prefs),
        { deep: true, immediate: true },
    );
}
