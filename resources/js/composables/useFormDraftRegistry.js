import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const STORAGE_PREFIX = 'ms:drafts';

/**
 * @typedef {object} FormDraftEntry
 * @property {string} key
 * @property {string} label
 * @property {string} routeName
 * @property {Record<string, unknown>} [routeParams]
 * @property {Record<string, unknown>} [query]
 * @property {Record<string, unknown>} data
 * @property {string} updatedAt ISO timestamp
 */

const drafts = ref([]);

function storageKey(userId, tenantId) {
    return `${STORAGE_PREFIX}:${userId ?? 'guest'}:${tenantId ?? '0'}`;
}

function readFromStorage(key) {
    try {
        const raw = localStorage.getItem(key);
        const parsed = raw ? JSON.parse(raw) : [];
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

function writeToStorage(key, entries) {
    try {
        if (entries.length === 0) {
            localStorage.removeItem(key);
        } else {
            localStorage.setItem(key, JSON.stringify(entries));
        }
    } catch {
        // localStorage unavailable — drafts are a convenience only.
    }
}

/**
 * Global reactive registry of in-progress form drafts (localStorage-backed).
 */
export function useFormDraftRegistry() {
    const page = usePage();

    const key = computed(() => storageKey(
        page.props.auth?.user?.id,
        page.props.auth?.user?.tenant_id,
    ));

    function reload() {
        drafts.value = readFromStorage(key.value);
    }

    /** @returns {FormDraftEntry|null} */
    function get(keyName) {
        return drafts.value.find((d) => d.key === keyName) ?? null;
    }

    /** @param {Omit<FormDraftEntry, 'updatedAt'> & { updatedAt?: string }} entry */
    function upsert(entry) {
        const next = {
            ...entry,
            updatedAt: entry.updatedAt ?? new Date().toISOString(),
        };
        const list = readFromStorage(key.value).filter((d) => d.key !== entry.key);
        list.unshift(next);
        writeToStorage(key.value, list);
        drafts.value = list;
    }

    function remove(keyName) {
        const list = readFromStorage(key.value).filter((d) => d.key !== keyName);
        writeToStorage(key.value, list);
        drafts.value = list;
    }

    const count = computed(() => drafts.value.length);

    reload();

    return {
        drafts,
        count,
        reload,
        get,
        upsert,
        remove,
    };
}
