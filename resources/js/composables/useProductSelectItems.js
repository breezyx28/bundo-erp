import { computed, unref } from 'vue';
import { useTrans } from '@/composables/useTrans';

export const PRODUCT_SELECT_FILTER_FIELDS = ['label', 'sku'];

/** Map API product options to USelectMenu items searchable by name and SKU. */
export function mapProductSelectItems(products) {
    return (products ?? []).map((p) => ({
        label: p.name,
        value: p.id,
        sku: p.sku ?? '',
    }));
}

/**
 * @param {import('vue').MaybeRefOrGetter<array>} productOptionsSource
 */
export function useProductSelectItems(productOptionsSource) {
    const { t } = useTrans();

    const productItems = computed(() =>
        mapProductSelectItems(unref(productOptionsSource)),
    );

    const productSelectAttrs = computed(() => ({
        searchable: true,
        descriptionKey: 'sku',
        filterFields: PRODUCT_SELECT_FILTER_FIELDS,
        searchInput: { placeholder: t('common.search_product') },
    }));

    return {
        productItems,
        productSelectAttrs,
        productFilterFields: PRODUCT_SELECT_FILTER_FIELDS,
    };
}
