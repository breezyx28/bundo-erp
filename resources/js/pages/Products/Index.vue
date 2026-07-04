<script setup>
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import FormModal from '@/components/FormModal.vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import TablePrintModal from '@/components/TablePrintModal.vue';
import { useTrans } from '@/composables/useTrans';
import { useTableFilters } from '@/composables/useTableFilters';
import { useTableColumns } from '@/composables/useTableColumns';
import { useResourceForm } from '@/composables/useResourceForm';

const props = defineProps({
    products: { type: Object, required: true },
    filters: { type: Object, default: () => ({ search: '', category: null, brand: null }) },
    sortOptions: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    brands: { type: Array, default: () => [] },
    canManage: { type: Boolean, default: false },
});

const { t } = useTrans();
const { filters, toggleSort } = useTableFilters('products.index', {
    search: props.filters.search ?? '',
    category: props.filters.category ?? null,
    brand: props.filters.brand ?? null,
    sort: props.filters.sort ?? '',
    direction: props.filters.direction ?? 'desc',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
});

const headers = [
    { key: 'image', label: '' },
    { key: 'name', label: t('fields.name'), sortable: true },
    { key: 'sku', label: t('fields.sku'), sortable: true },
    { key: 'category', label: t('fields.category') },
    { key: 'selling_price', label: t('fields.selling_price'), sortable: true, align: 'end' },
    { key: 'is_active', label: t('common.status'), sortable: true },
];

const { visibleHeaders, columnOptions, toggle: toggleColumn } = useTableColumns('products.index', headers);
const printOpen = ref(false);
const printRows = computed(() =>
    (props.products.data ?? []).map((row) => ({
        ...row,
        category: row.category || '—',
        selling_price: row.selling_price_formatted,
        is_active: row.is_active ? t('common.active') : t('common.inactive'),
    })),
);

const selectItems = (items, allLabel) => [
    { label: allLabel, value: null },
    ...items.map((item) => ({ label: item.name, value: item.id })),
];

const categoryItems = computed(() => selectItems(props.categories, t('fields.category')));
const brandItems = computed(() => selectItems(props.brands, t('fields.brand')));
const categoryFormItems = computed(() => selectItems(props.categories, '—'));
const brandFormItems = computed(() => selectItems(props.brands, '—'));

const form = useForm({
    name: '',
    sku: '',
    barcode: '',
    category_id: null,
    brand_id: null,
    unit: 'pair',
    cost_price: 0,
    selling_price: 0,
    reorder_level: 0,
    description: '',
    is_active: true,
    has_variants: false,
    variants: [],
    image: null,
});

const {
    modalOpen,
    editingId,
    deleteOpen,
    deleting,
    openCreate,
    openEdit,
    askDelete,
    destroy,
} = useResourceForm(form, {
    resource: 'products',
    only: [
        'name', 'sku', 'barcode', 'category_id', 'brand_id', 'unit',
        'cost_price', 'selling_price', 'reorder_level', 'description',
        'is_active', 'has_variants', 'variants',
    ],
});

function onImageChange(event) {
    form.image = event.target.files[0] ?? null;
}

function addVariant() {
    form.variants.push({
        id: null,
        sku: '',
        size: '',
        color: '',
        cost_price: form.cost_price,
        selling_price: form.selling_price,
    });
}

function removeVariant(index) {
    form.variants.splice(index, 1);
}

const toBooleans = (data) => ({
    ...data,
    is_active: data.is_active ? 1 : 0,
    has_variants: data.has_variants ? 1 : 0,
});

function submit() {
    const withFile = form.image instanceof File;
    const options = {
        preserveScroll: true,
        forceFormData: withFile,
        onSuccess: () => {
            modalOpen.value = false;
        },
    };

    if (editingId.value) {
        if (withFile) {
            form.transform((data) => ({ ...toBooleans(data), _method: 'put' }))
                .post(route('products.update', editingId.value), options);
        } else {
            form.transform(toBooleans).put(route('products.update', editingId.value), options);
        }
    } else {
        form.transform(toBooleans).post(route('products.store'), options);
    }
}
</script>

<template>
    <AppLayout :title="t('nav.products')">
        <Head :title="t('nav.products')" />

        <div class="space-y-6">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-xl font-semibold text-highlighted">
                    {{ t('nav.products') }}
                </h1>
                <UButton
                    v-if="canManage"
                    :label="t('common.create')"
                    icon="i-heroicons-plus"
                    @click="openCreate()"
                />
            </div>

            <UCard>
                <DataTable
                    :headers="visibleHeaders"
                    :rows="products"
                    :query="filters"
                    :sort="filters.sort"
                    :direction="filters.direction"
                    :actions="canManage"
                    @sort="toggleSort"
                >
                    <template #toolbar>
                        <TableToolbar
                            :filters="filters"
                            :sort-options="sortOptions"
                            :column-options="columnOptions"
                            @toggle-column="toggleColumn"
                            @print="printOpen = true"
                        >
                            <template #filters>
                                <USelectMenu
                                    v-model="filters.category"
                                    :items="categoryItems"
                                    value-key="value"
                                    class="w-full sm:w-44"
                                />
                                <USelectMenu
                                    v-model="filters.brand"
                                    :items="brandItems"
                                    value-key="value"
                                    class="w-full sm:w-44"
                                />
                            </template>
                        </TableToolbar>
                    </template>

                    <template #cell-image="{ row }">
                        <div class="size-10 overflow-hidden rounded-md bg-elevated">
                            <img v-if="row.image" :src="row.image" class="size-full object-cover" alt="" />
                            <div v-else class="flex size-full items-center justify-center text-dimmed">
                                <UIcon name="i-heroicons-cube" class="size-5" />
                            </div>
                        </div>
                    </template>

                    <template #cell-name="{ row }">
                        <div>
                            <div class="text-xs text-muted">{{ row.sku }}</div>
                            <div class="font-medium">{{ row.name }}</div>
                        </div>
                    </template>

                    <template #cell-category="{ value }">
                        {{ value || '—' }}
                    </template>

                    <template #cell-selling_price="{ row }">
                        {{ row.selling_price_formatted }}
                    </template>

                    <template #cell-is_active="{ row }">
                        <UBadge
                            :color="row.is_active ? 'success' : 'neutral'"
                            variant="subtle"
                            :label="row.is_active ? t('common.active') : t('common.inactive')"
                        />
                    </template>

                    <template #actions="{ row }">
                        <UButton icon="i-heroicons-pencil-square" color="neutral" variant="ghost" size="sm" @click="openEdit(row)" />
                        <UButton icon="i-heroicons-trash" color="error" variant="ghost" size="sm" @click="askDelete(row.id)" />
                    </template>
                </DataTable>
            </UCard>
        </div>

        <FormModal
            v-model:open="modalOpen"
            :title="editingId ? t('common.edit') : t('common.create')"
            width="sm:max-w-3xl"
        >
            <div class="grid gap-4 sm:grid-cols-2">
                <UFormField :label="t('fields.name')" :error="form.errors.name">
                    <UInput v-model="form.name" class="w-full" />
                </UFormField>
                <UFormField :label="t('fields.sku')" :error="form.errors.sku">
                    <UInput v-model="form.sku" :placeholder="t('common.none')" class="w-full" />
                </UFormField>
                <UFormField :label="t('fields.category')" :error="form.errors.category_id">
                    <USelectMenu v-model="form.category_id" :items="categoryFormItems" value-key="value" class="w-full" />
                </UFormField>
                <UFormField :label="t('fields.brand')" :error="form.errors.brand_id">
                    <USelectMenu v-model="form.brand_id" :items="brandFormItems" value-key="value" class="w-full" />
                </UFormField>
                <UFormField :label="t('fields.cost_price')" :error="form.errors.cost_price">
                    <UInput v-model="form.cost_price" type="number" step="0.01" class="w-full" />
                </UFormField>
                <UFormField :label="t('fields.selling_price')" :error="form.errors.selling_price">
                    <UInput v-model="form.selling_price" type="number" step="0.01" class="w-full" />
                </UFormField>
                <UFormField :label="t('fields.unit')" :error="form.errors.unit">
                    <UInput v-model="form.unit" class="w-full" />
                </UFormField>
                <UFormField :label="t('fields.reorder_level')" :error="form.errors.reorder_level">
                    <UInput v-model="form.reorder_level" type="number" class="w-full" />
                </UFormField>
                <UFormField :label="t('fields.barcode')" :error="form.errors.barcode">
                    <UInput v-model="form.barcode" class="w-full" />
                </UFormField>
                <UFormField :label="t('fields.image')" :error="form.errors.image">
                    <input
                        type="file"
                        accept="image/*"
                        class="block w-full text-sm text-muted file:me-3 file:rounded-md file:border-0 file:bg-elevated file:px-3 file:py-1.5 file:text-sm"
                        @change="onImageChange"
                    />
                </UFormField>
                <UFormField :label="t('fields.description')" :error="form.errors.description" class="sm:col-span-2">
                    <UTextarea v-model="form.description" :rows="2" class="w-full" />
                </UFormField>
                <UCheckbox v-model="form.is_active" :label="t('common.active')" />
                <UCheckbox v-model="form.has_variants" :label="t('fields.has_variants')" />
            </div>

            <div v-if="form.has_variants" class="mt-5 space-y-3 rounded-xl border border-default p-4">
                <div class="flex items-center justify-between">
                    <span class="font-medium">{{ t('fields.variants') }}</span>
                    <UButton size="sm" icon="i-heroicons-plus" :label="t('common.create')" @click="addVariant" />
                </div>
                <div
                    v-for="(variant, index) in form.variants"
                    :key="index"
                    class="grid items-end gap-2 sm:grid-cols-12"
                >
                    <UFormField :label="t('fields.sku')" class="sm:col-span-3">
                        <UInput v-model="variant.sku" class="w-full" />
                    </UFormField>
                    <UFormField :label="t('fields.size')" class="sm:col-span-2">
                        <UInput v-model="variant.size" class="w-full" />
                    </UFormField>
                    <UFormField :label="t('fields.color')" class="sm:col-span-3">
                        <UInput v-model="variant.color" class="w-full" />
                    </UFormField>
                    <UFormField :label="t('fields.selling_price')" class="sm:col-span-3">
                        <UInput v-model="variant.selling_price" type="number" step="0.01" class="w-full" />
                    </UFormField>
                    <UButton
                        icon="i-heroicons-trash"
                        color="error"
                        variant="ghost"
                        size="sm"
                        class="sm:col-span-1"
                        @click="removeVariant(index)"
                    />
                </div>
            </div>

            <template #footer="{ close }">
                <UButton color="neutral" variant="ghost" :label="t('common.cancel')" @click="close" />
                <UButton :label="t('common.save')" :loading="form.processing" @click="submit()" />
            </template>
        </FormModal>

        <ConfirmModal v-model:open="deleteOpen" :loading="deleting" @confirm="destroy()" />

        <TablePrintModal
            v-model:open="printOpen"
            :title="t('nav.products')"
            :headers="visibleHeaders"
            :rows="printRows"
        />
    </AppLayout>
</template>
