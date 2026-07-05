import { ref } from 'vue';

const open = ref(false);

/** Shared open state so inline + floating triggers share one modal. */
export function useFormDraftModal() {
    function openModal() {
        open.value = true;
    }

    function closeModal() {
        open.value = false;
    }

    return {
        open,
        openModal,
        closeModal,
    };
}
