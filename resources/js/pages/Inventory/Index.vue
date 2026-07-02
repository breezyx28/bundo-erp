<script setup>
import { computed, ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import FormModal from '@/components/FormModal.vue';
import { useTrans } from '@/composables/useTrans';
import { useTableFilters } from '@/composables/useTableFilters';

const props = defineProps({
    products: { type: Object, required: true },
    productOptions: { type: Array, default: () => [] },
    variantsByProduct: { type: Object, default: () => ({}) },
    locationOptions: { type: Array, default: () => [] },
    branchName: { type: String, default: null },
    isConsolidated: { type: Boolean, default: false },
    movements: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    canReceive: { type: Boolean, default: false },
    canAdjust: { type: Boolean, default: false },
});

const { t } = useTrans();
const { filters } = useTableFilters('inventory.index', {
    search: props.filters.search ?? '',
    low_stock: props.filters.low_stock ?? false,
});

const headers = [
    { key: 'name', label: t('fields.name') },
    { key: 'sku', label: t('fields.sku') },
    { key: 'on_hand', label: t('inventory.on_hand'), class: 'text-end' },
    { key: 'reorder_level', label: t('fields.reorder_level'), class: 'text-end' },
];

const productItems = computed(() =>
    props.productOptions.map((p) => ({ label: p.name, value: p.id })),
);
const locationItems = computed(() => [
    { label: t('common.none'), value: null },
    ...props.locationOptions.map((l) => ({ label: l.name, value: l.id })),
]);

const variantItems = (productId) => {
    const list = props.variantsByProduct[productId] ?? [];
    return list.map((v) => ({ label: v.name, value: v.id }));
};

// Receive
const receiveOpen = ref(false);
const receiveForm = useForm({
    r_product_id: null,
    r_variant_id: null,
    r_location_id: null,
    r_quantity: 1,
    r_unit_cost: 0,
    r_batch_number: '',
});
const receiveVariants = computed(() => variantItems(receiveForm.r_product_id));

function openReceive() {
    receiveForm.reset();
    receiveForm.clearErrors();
    receiveOpen.value = true;
}

function submitReceive() {
    receiveForm.post(route('inventory.receive'), {
        preserveScroll: true,
        onSuccess: () => {
            receiveOpen.value = false;
        },
    });
}

// Adjust
const adjustOpen = ref(false);
const adjustForm = useForm({
    a_product_id: null,
    a_variant_id: null,
    a_quantity: 0,
    a_reason: '',
});
const adjustVariants = computed(() => variantItems(adjustForm.a_product_id));

function openAdjust(row) {
    adjustForm.reset();
    adjustForm.clearErrors();
    adjustForm.a_product_id = row.id;
    adjustForm.a_quantity = row.on_hand;
    adjustOpen.value = true;
}

function submitAdjust() {
    adjustForm.post(route('inventory.adjust'), {
        preserveScroll: true,
        onSuccess: () => {
            adjustOpen.value = false;
        },
    });
}

// Movements
const movementsOpen = ref(false);
const movementsName = ref('');
const movementsLoading = ref(false);

function openMovements(row) {
    movementsName.value = row.name;
    movementsOpen.value = true;
    movementsLoading.value = true;
    router.reload({
        only: ['movements'],
        data: { movement_product: row.id },
        onFinish: () => {
            movementsLoading.value = false;
        },
    });
}
</script>

<template>
    <AppLayout :title="t('nav.inventory')">
        <Head :title="t('nav.inventory')" />

        <div class="space-y-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold text-highlighted">
                        {{ t('nav.inventory') }}
                    </h1>
                    <p v-if="isConsolidated" class="text-sm text-warning">
                        {{ t('inventory.consolidated_hint') }}
                    </p>
                    <p v-else-if="branchName" class="text-sm text-muted">
                        {{ branchName }}
                    </p>
                </div>
                <UButton
                    v-if="canReceive"
                    :label="t('inventory.receive_stock')"
                    icon="i-heroicons-arrow-down-tray"
                    @click="openReceive()"
                />
            </div>

            <UCard>
                <DataTable :headers="headers" :rows="products" :query="filters" actions>
                    <template #toolbar>
                        <UInput
                            v-model="filters.search"
                            icon="i-heroicons-magnifying-glass"
                            :placeholder="t('common.search')"
                            class="w-full sm:max-w-xs"
                        />
                        <UCheckbox v-model="filters.low_stock" :label="t('inventory.low_stock_only')" />
                    </template>

                    <template #cell-on_hand="{ row }">
                        <div class="text-end tabular-nums">
                            <span class="font-semibold">{{ row.on_hand.toLocaleString() }}</span>
                            <UBadge
                                v-if="row.low_stock"
                                color="warning"
                                variant="subtle"
                                size="sm"
                                class="ms-1"
                                :label="t('inventory.low')"
                            />
                        </div>
                    </template>

                    <template #cell-reorder_level="{ row }">
                        <span class="text-end tabular-nums text-muted">{{ row.reorder_level.toLocaleString() }}</span>
                    </template>

                    <template #actions="{ row }">
                        <UButton
                            icon="i-heroicons-clock"
                            color="neutral"
                            variant="ghost"
                            size="sm"
                            @click="openMovements(row)"
                        />
                        <UButton
                            v-if="canAdjust"
                            icon="i-heroicons-adjustments-horizontal"
                            color="neutral"
                            variant="ghost"
                            size="sm"
                            @click="openAdjust(row)"
                        />
                    </template>
                </DataTable>
            </UCard>
        </div>

        <FormModal v-model:open="receiveOpen" :title="t('inventory.receive_stock')" width="sm:max-w-xl">
            <div class="grid gap-4">
                <UFormField :label="t('nav.products')" :error="receiveForm.errors.r_product_id">
                    <USelectMenu
                        v-model="receiveForm.r_product_id"
                        :items="productItems"
                        value-key="value"
                        searchable
                        :placeholder="t('common.none')"
                        class="w-full"
                    />
                </UFormField>
                <UFormField v-if="receiveVariants.length" :label="t('fields.variants')" :error="receiveForm.errors.r_variant_id">
                    <USelectMenu
                        v-model="receiveForm.r_variant_id"
                        :items="receiveVariants"
                        value-key="value"
                        :placeholder="t('common.none')"
                        class="w-full"
                    />
                </UFormField>
                <div class="grid gap-4 sm:grid-cols-2">
                    <UFormField :label="t('inventory.quantity')" :error="receiveForm.errors.r_quantity">
                        <UInput v-model="receiveForm.r_quantity" type="number" min="1" class="w-full" />
                    </UFormField>
                    <UFormField :label="t('fields.cost_price')" :error="receiveForm.errors.r_unit_cost">
                        <UInput v-model="receiveForm.r_unit_cost" type="number" step="0.01" class="w-full" />
                    </UFormField>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <UFormField :label="t('inventory.location')" :error="receiveForm.errors.r_location_id">
                        <USelectMenu v-model="receiveForm.r_location_id" :items="locationItems" value-key="value" class="w-full" />
                    </UFormField>
                    <UFormField :label="t('inventory.batch_number')" :error="receiveForm.errors.r_batch_number">
                        <UInput v-model="receiveForm.r_batch_number" :placeholder="t('inventory.batch_auto')" class="w-full" />
                    </UFormField>
                </div>
            </div>

            <template #footer="{ close }">
                <UButton color="neutral" variant="ghost" :label="t('common.cancel')" @click="close" />
                <UButton :label="t('inventory.receive_stock')" :loading="receiveForm.processing" @click="submitReceive()" />
            </template>
        </FormModal>

        <FormModal v-model:open="adjustOpen" :title="t('inventory.adjust_stock')" width="sm:max-w-lg">
            <div class="grid gap-4">
                <UFormField v-if="adjustVariants.length" :label="t('fields.variants')" :error="adjustForm.errors.a_variant_id">
                    <USelectMenu
                        v-model="adjustForm.a_variant_id"
                        :items="adjustVariants"
                        value-key="value"
                        :placeholder="t('common.none')"
                        class="w-full"
                    />
                </UFormField>
                <UFormField :label="t('inventory.new_quantity')" :error="adjustForm.errors.a_quantity" :hint="t('inventory.adjust_hint')">
                    <UInput v-model="adjustForm.a_quantity" type="number" min="0" class="w-full" />
                </UFormField>
                <UFormField :label="t('inventory.reason')" :error="adjustForm.errors.a_reason">
                    <UTextarea v-model="adjustForm.a_reason" :rows="2" class="w-full" />
                </UFormField>
            </div>

            <template #footer="{ close }">
                <UButton color="neutral" variant="ghost" :label="t('common.cancel')" @click="close" />
                <UButton :label="t('common.save')" :loading="adjustForm.processing" @click="submitAdjust()" />
            </template>
        </FormModal>

        <USlideover v-model:open="movementsOpen" :title="movementsName" :description="t('inventory.movements')">
            <template #body>
                <div v-if="movementsLoading" class="py-10 text-center text-muted">
                    <UIcon name="i-heroicons-arrow-path" class="size-6 animate-spin" />
                </div>
                <div v-else class="flex flex-col gap-2">
                    <div
                        v-for="m in movements"
                        :key="m.id"
                        class="flex items-center justify-between rounded-lg border border-default p-3"
                    >
                        <div>
                            <UBadge
                                :color="m.quantity_change >= 0 ? 'success' : 'error'"
                                variant="subtle"
                                size="sm"
                                :label="m.type_label"
                            />
                            <div class="mt-1 text-xs text-muted">
                                {{ m.created_at }} · {{ m.user ?? '—' }}
                            </div>
                            <div v-if="m.reason" class="text-xs text-dimmed">{{ m.reason }}</div>
                        </div>
                        <div
                            class="font-semibold tabular-nums"
                            :class="m.quantity_change >= 0 ? 'text-success' : 'text-error'"
                        >
                            {{ m.quantity_change >= 0 ? '+' : '' }}{{ m.quantity_change.toLocaleString() }}
                        </div>
                    </div>
                    <div v-if="!movements.length" class="py-6 text-center text-muted">
                        {{ t('common.no_results') }}
                    </div>
                </div>
            </template>
        </USlideover>
    </AppLayout>
</template>
