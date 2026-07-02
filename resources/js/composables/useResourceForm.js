import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route as ziggyRoute } from 'ziggy-js';

/**
 * Standard create/edit-modal + delete-confirm workflow for a RESTful resource.
 *
 * @param {import('@inertiajs/vue3').InertiaForm} form  Inertia useForm() instance.
 * @param {object} options
 * @param {string} options.resource  Route name base (e.g. 'brands' -> brands.store/update/destroy).
 * @param {string[]} [options.only]  Fields to copy from a row when editing.
 */
export function useResourceForm(form, { resource, only } = {}) {
    const modalOpen = ref(false);
    const editingId = ref(null);
    const deleteOpen = ref(false);
    const deleteId = ref(null);
    const deleting = ref(false);

    function fieldKeys() {
        return only ?? Object.keys(form.data());
    }

    function openCreate() {
        editingId.value = null;
        form.reset();
        form.clearErrors();
        modalOpen.value = true;
    }

    function openEdit(row) {
        editingId.value = row.id;
        form.reset();
        form.clearErrors();
        for (const key of fieldKeys()) {
            if (key in row && row[key] !== null && row[key] !== undefined) {
                form[key] = row[key];
            }
        }
        modalOpen.value = true;
    }

    function submit(overrides = {}) {
        const options = {
            preserveScroll: true,
            onSuccess: () => {
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
    };
}
