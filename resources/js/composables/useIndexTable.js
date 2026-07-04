import { ref } from 'vue';
import { useTableFilters } from './useTableFilters';
import { useTableColumns } from './useTableColumns';

/**
 * Shared wiring for index pages: server filters, column visibility, and print modal state.
 */
export function useIndexTable(routeName, headers, initialFilters = {}, options = {}) {
    const storageKey = options.storageKey ?? routeName;
    const { filters, toggleSort } = useTableFilters(routeName, initialFilters, options);
    const { visibleHeaders, columnOptions, toggle: toggleColumn } = useTableColumns(storageKey, headers);
    const printOpen = ref(false);

    return {
        filters,
        toggleSort,
        visibleHeaders,
        columnOptions,
        toggleColumn,
        printOpen,
    };
}
