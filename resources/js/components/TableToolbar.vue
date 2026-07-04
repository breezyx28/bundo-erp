<script setup>
import { computed } from 'vue';
import { useTrans } from '@/composables/useTrans';

const props = defineProps({
    // Reactive filters object from useTableFilters.
    filters: { type: Object, required: true },
    // Sortable columns: [{ value, label }].
    sortOptions: { type: Array, default: () => [] },
    // Column visibility options from useTableColumns.columnOptions.
    columnOptions: { type: Array, default: () => [] },
    // Whether to show the date-range pickers.
    dateRange: { type: Boolean, default: true },
    searchPlaceholder: { type: String, default: '' },
    // Whether to show the search input (some pages have a custom one).
    search: { type: Boolean, default: true },
});

const emit = defineEmits(['toggle-column', 'print']);

const { t } = useTrans();

const directionItems = computed(() => [
    { value: 'asc', label: t('common.sort_asc'), icon: 'i-heroicons-bars-arrow-up' },
    { value: 'desc', label: t('common.sort_desc'), icon: 'i-heroicons-bars-arrow-down' },
]);

const sortItems = computed(() => [
    { value: '', label: t('common.default') },
    ...props.sortOptions,
]);

const hasHiddenSupport = computed(() => props.columnOptions.length > 0);
</script>

<template>
    <div class="flex w-full flex-wrap items-center gap-2">
        <UInput
            v-if="search"
            v-model="filters.search"
            icon="i-heroicons-magnifying-glass"
            :placeholder="searchPlaceholder || t('common.search')"
            class="w-full sm:max-w-xs"
        />

        <slot name="filters" />

        <template v-if="dateRange">
            <UInput v-model="filters.date_from" type="date" class="w-40" :aria-label="t('common.date_from')" />
            <span class="text-dimmed">–</span>
            <UInput v-model="filters.date_to" type="date" class="w-40" :aria-label="t('common.date_to')" />
        </template>

        <USelectMenu
            v-if="sortOptions.length"
            v-model="filters.sort"
            :items="sortItems"
            value-key="value"
            :placeholder="t('common.sort_by')"
            class="w-40"
        />
        <USelectMenu
            v-if="sortOptions.length"
            v-model="filters.direction"
            :items="directionItems"
            value-key="value"
            class="w-36"
        />

        <div class="ms-auto flex items-center gap-2">
            <UPopover v-if="hasHiddenSupport">
                <UButton
                    icon="i-heroicons-view-columns"
                    color="neutral"
                    variant="outline"
                    :label="t('common.columns')"
                />
                <template #content>
                    <div class="w-56 space-y-1 p-2">
                        <UCheckbox
                            v-for="col in columnOptions"
                            :key="col.key"
                            :model-value="col.visible"
                            :label="col.label"
                            @update:model-value="emit('toggle-column', col.key)"
                        />
                    </div>
                </template>
            </UPopover>

            <UButton
                icon="i-heroicons-printer"
                color="neutral"
                variant="outline"
                :label="t('common.print')"
                @click="emit('print')"
            />
        </div>
    </div>
</template>
