import { computed, onMounted, ref, toValue, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useFormDraftRegistry } from '@/composables/useFormDraftRegistry';
import { useTrans } from '@/composables/useTrans';

/**
 * Persist an Inertia form to localStorage while the user is editing.
 *
 * @param {object} options
 * @param {string|import('vue').Ref<string>} options.key
 * @param {string|import('vue').Ref<string>} options.label
 * @param {string} options.routeName
 * @param {import('@inertiajs/vue3').InertiaForm} options.form
 * @param {Record<string, unknown>} [options.routeParams]
 * @param {import('vue').Ref<boolean>} [options.active]
 * @param {(data: Record<string, unknown>) => void} [options.onApply]
 * @param {() => Record<string, unknown>} [options.getSnapshot]
 */
export function useFormDraft({
    key,
    label,
    routeName,
    form,
    routeParams = {},
    active,
    apply,
    onApply,
    getSnapshot,
    isEmpty,
}) {
    const toast = useToast();
    const { t } = useTrans();
    const { upsert, remove, get } = useFormDraftRegistry();

    const resolvedKey = computed(() => toValue(key));
    const resolvedLabel = computed(() => toValue(label));

    let saveTimer = null;

    function snapshot() {
        const data = getSnapshot ? getSnapshot() : form.data();
        return JSON.parse(JSON.stringify(data));
    }

    function shouldSkipSave() {
        if (!resolvedKey.value) {
            return true;
        }
        if (active && !active.value) {
            return true;
        }
        if (isEmpty?.()) {
            return true;
        }
        const data = form.data();
        return Object.values(data).every((v) => v === null || v === '' || v === 0 || v === false
            || (Array.isArray(v) && v.length === 0));
    }

    function saveDraft() {
        if (shouldSkipSave()) {
            if (resolvedKey.value) {
                remove(resolvedKey.value);
            }
            return;
        }
        upsert({
            key: resolvedKey.value,
            label: resolvedLabel.value,
            routeName,
            routeParams,
            data: snapshot(),
        });
    }

    function debouncedSave() {
        if (saveTimer) {
            clearTimeout(saveTimer);
        }
        saveTimer = setTimeout(saveDraft, 400);
    }

    function clearDraft() {
        if (resolvedKey.value) {
            remove(resolvedKey.value);
        }
    }

    function restoreDraft(showToast = true) {
        const entry = get(resolvedKey.value);
        if (!entry?.data) {
            return false;
        }
        if (apply) {
            apply(entry.data);
        } else if (onApply) {
            onApply(entry.data);
        } else {
            Object.keys(entry.data).forEach((field) => {
                if (field in form) {
                    form[field] = entry.data[field];
                }
            });
        }
        if (showToast) {
            toast.add({
                title: t('common.draft_restored'),
                color: 'info',
            });
        }
        return true;
    }

    watch(
        () => form.data(),
        () => debouncedSave(),
        { deep: true },
    );

    if (active) {
        watch(active, () => debouncedSave());
    }

    watch(resolvedKey, () => debouncedSave());

    onMounted(() => {
        const params = new URLSearchParams(window.location.search);
        const draftParam = params.get('draft');
        if (draftParam && draftParam === resolvedKey.value) {
            restoreDraft(true);
        }
    });

    return {
        saveDraft,
        clearDraft,
        restoreDraft,
        hasDraft: () => (resolvedKey.value ? get(resolvedKey.value) !== null : false),
    };
}

/**
 * Restore a draft from ?draft= query and open modal if key matches prefix.
 *
 * @param {string} prefix e.g. products
 * @param {(key: string) => void} onMatch
 */
export function useDraftQueryRestore(prefix, onMatch) {
    onMounted(() => {
        const params = new URLSearchParams(window.location.search);
        const draftParam = params.get('draft');
        if (draftParam?.startsWith(prefix)) {
            onMatch(draftParam);
        }
    });
}
