import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route as ziggyRoute } from 'ziggy-js';
import { useFormDraft, useDraftQueryRestore } from '@/composables/useFormDraft';

/**
 * Standard create/edit-modal + delete-confirm workflow for a RESTful resource.
 */
export function useResourceForm(form, { resource, only, draftKey, draftLabel } = {}) {
    const modalOpen = ref(false);
    const editingId = ref(null);
    const deleteOpen = ref(false);
    const deleteId = ref(null);
    const deleting = ref(false);

    const activeDraftKey = ref(draftKey ? `${draftKey}.create` : '');
    const activeDraftLabel = ref(draftLabel ?? '');

    const draft = draftKey
        ? useFormDraft({
            key: activeDraftKey,
            label: activeDraftLabel,
            routeName: `${resource}.index`,
            form,
            active: modalOpen,
        })
        : null;

    if (draftKey) {
        useDraftQueryRestore(draftKey, (key) => {
            activeDraftKey.value = key;
            activeDraftLabel.value = key.includes('.edit:')
                ? `${draftLabel} (${key.split(':')[1]})`
                : draftLabel;
            if (draft?.restoreDraft(true)) {
                modalOpen.value = true;
            }
        });
    }

    function fieldKeys() {
        return only ?? Object.keys(form.data());
    }

    function openCreate() {
        editingId.value = null;
        if (draftKey) {
            activeDraftKey.value = `${draftKey}.create`;
            activeDraftLabel.value = draftLabel;
        }
        form.reset();
        form.clearErrors();
        draft?.restoreDraft(false);
        modalOpen.value = true;
    }

    function openEdit(row) {
        editingId.value = row.id;
        if (draftKey) {
            activeDraftKey.value = `${draftKey}.edit:${row.id}`;
            activeDraftLabel.value = `${draftLabel} (#${row.id})`;
        }
        form.reset();
        form.clearErrors();
        for (const key of fieldKeys()) {
            if (key in row && row[key] !== null && row[key] !== undefined) {
                form[key] = row[key];
            }
        }
        draft?.restoreDraft(false);
        modalOpen.value = true;
    }

    function submit(overrides = {}) {
        const options = {
            preserveScroll: true,
            onSuccess: () => {
                draft?.clearDraft();
                modalOpen.value = false;
            },
            ...overrides,
        };

        if (editingId.value) {
            form.put(ziggyRoute(`${resource}.update`, editingId.value), options);
        } else {
            form.post(ziggyRoute(`${resource}.store`), options);
        }
    }

    function askDelete(id) {
        deleteId.value = id;
        deleteOpen.value = true;
    }

    function closeDelete() {
        deleteOpen.value = false;
        deleteId.value = null;
    }

    function destroy() {
        if (!deleteId.value) {
            return;
        }
        deleting.value = true;
        router.delete(ziggyRoute(`${resource}.destroy`, deleteId.value), {
            preserveScroll: true,
            onFinish: () => {
                deleting.value = false;
                closeDelete();
            },
        });
    }

    return {
        modalOpen,
        editingId,
        deleteOpen,
        deleteId,
        deleting,
        openCreate,
        openEdit,
        submit,
        askDelete,
        closeDelete,
        destroy,
        clearDraft: () => draft?.clearDraft(),
    };
}
