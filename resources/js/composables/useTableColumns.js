import { computed, ref, watch } from 'vue';

/**
 * Per-page column visibility and print selection, persisted in localStorage.
 *
 * @param {string} storageKey  Unique key per table (e.g. 'sales.index').
 * @param {Array}  headers     The table's header definitions [{ key, label, ... }].
 */
export function useTableColumns(storageKey, headers) {
    const allKeys = headers.map((h) => h.key);
    const lsKey = `table-columns:${storageKey}`;

    const hidden = ref(loadHidden());

    function loadHidden() {
        try {
            const raw = localStorage.getItem(lsKey);
            const parsed = raw ? JSON.parse(raw) : [];
            return Array.isArray(parsed) ? parsed.filter((k) => allKeys.includes(k)) : [];
        } catch {
            return [];
        }
    }

    watch(
        hidden,
        (value) => {
            try {
                localStorage.setItem(lsKey, JSON.stringify(value));
            } catch {
                // Ignore quota/availability errors — visibility is a convenience.
            }
        },
        { deep: true },
    );

    const visibleHeaders = computed(() =>
        headers.filter((h) => !hidden.value.includes(h.key)),
    );

    function isVisible(key) {
        return !hidden.value.includes(key);
    }

    function toggle(key) {
        if (hidden.value.includes(key)) {
            hidden.value = hidden.value.filter((k) => k !== key);
        } else {
            hidden.value = [...hidden.value, key];
        }
    }

    // Options for a checkbox list / popover.
    const columnOptions = computed(() =>
        headers.map((h) => ({ key: h.key, label: h.label, visible: isVisible(h.key) })),
    );

    return { hidden, visibleHeaders, columnOptions, isVisible, toggle };
}
