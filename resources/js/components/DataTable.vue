<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useTrans } from '@/composables/useTrans';

const props = defineProps({
    // [{ key, label, class?, align? }]
    headers: { type: Array, required: true },
    // Laravel paginator serialized to array (data, links, current_page, ...)
    // or a plain array of rows.
    rows: { type: [Object, Array], required: true },
    striped: { type: Boolean, default: false },
    rowKey: { type: String, default: 'id' },
    // Extra query params to preserve across pagination (e.g. filters).
    query: { type: Object, default: () => ({}) },
    // Whether an actions column should be rendered.
    actions: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
});

const { t } = useTrans();

const isPaginator = computed(
    () => !Array.isArray(props.rows) && Array.isArray(props.rows?.data),
);

const items = computed(() =>
    isPaginator.value ? props.rows.data : props.rows || [],
);

const colspan = computed(
    () => props.headers.length + (props.actions ? 1 : 0),
);

function goToPage(page) {
    const path = props.rows.path || window.location.pathname;

    router.get(
        path,
        { ...props.query, page },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}
</script>

<template>
    <div class="w-full">
        <div
            v-if="$slots.toolbar"
            class="mb-4 flex flex-wrap items-center gap-3"
        >
            <slot name="toolbar" />
        </div>

        <div class="relative overflow-x-auto rounded-lg border border-default">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-default bg-elevated/50 text-start">
                        <th
                            v-for="header in headers"
                            :key="header.key"
                            class="px-4 py-3 text-start font-medium text-muted whitespace-nowrap"
                            :class="header.class"
                        >
                            {{ header.label }}
                        </th>
                        <th
                            v-if="actions"
                            class="px-4 py-3 text-end font-medium text-muted"
                        >
                            {{ t('common.actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(row, index) in items"
                        :key="row[rowKey] ?? index"
                        class="border-b border-default last:border-0 transition-colors hover:bg-elevated/40"
                        :class="striped && index % 2 ? 'bg-elevated/20' : ''"
                    >
                        <td
                            v-for="header in headers"
                            :key="header.key"
                            class="px-4 py-3 align-middle"
                            :class="header.class"
                        >
                            <slot
                                :name="`cell-${header.key}`"
                                :row="row"
                                :value="row[header.key]"
                            >
                                {{ row[header.key] }}
                            </slot>
                        </td>
                        <td v-if="actions" class="px-4 py-3 text-end align-middle">
                            <div class="flex items-center justify-end gap-1">
                                <slot name="actions" :row="row" />
                            </div>
                        </td>
                    </tr>

                    <tr v-if="!items.length">
                        <td
                            :colspan="colspan"
                            class="px-4 py-10 text-center text-muted"
                        >
                            <slot name="empty">{{ t('common.no_results') }}</slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="isPaginator && rows.last_page > 1"
            class="mt-4 flex items-center justify-between gap-4"
        >
            <p class="text-xs text-muted">
                {{ rows.from }}–{{ rows.to }} / {{ rows.total }}
            </p>
            <UPagination
                :page="rows.current_page"
                :items-per-page="rows.per_page"
                :total="rows.total"
                @update:page="goToPage"
            />
        </div>
    </div>
</template>
