import { ref } from 'vue';

const open = ref(false);

/** Shared open state for calculator FAB + keyboard shortcut. */
export function useCalculatorModal() {
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
