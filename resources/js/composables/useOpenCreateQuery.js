import { onMounted } from 'vue';

/**
 * Opens a page's create modal when visited with ?open=create
 * (used by quick actions from the Links page / launcher / command palette).
 */
export function useOpenCreateQuery(openCreate, enabled = () => true) {
    onMounted(() => {
        const params = new URLSearchParams(window.location.search);
        if (params.get('open') === 'create' && enabled()) {
            openCreate();
        }
    });
}
