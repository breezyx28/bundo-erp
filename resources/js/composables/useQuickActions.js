import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useTrans } from './useTrans';

const DEFINITIONS = [
    { key: 'new_sale', icon: 'i-heroicons-shopping-cart', routeName: 'sales.index', permission: 'invoices.create' },
    { key: 'new_purchase', icon: 'i-heroicons-truck', routeName: 'purchases.index', permission: 'purchases.create' },
    { key: 'new_customer', icon: 'i-heroicons-user-plus', routeName: 'customers.index', permission: 'customers.create' },
    { key: 'new_product', icon: 'i-heroicons-cube', routeName: 'products.index', permission: 'products.create' },
    { key: 'new_expense', icon: 'i-heroicons-banknotes', routeName: 'expenses.index', permission: 'expenses.create' },
];

/**
 * Permission-filtered shortcuts that open a page with its create modal
 * (via ?open=create). Used by the Links page, launcher, and command palette.
 */
export function useQuickActions() {
    const page = usePage();
    const { t } = useTrans();

    const actions = computed(() => {
        const user = page.props.auth?.user;
        const permissions = user?.permissions ?? [];

        return DEFINITIONS.filter(
            (action) => user?.is_super_admin || permissions.includes(action.permission),
        ).map((action) => ({
            key: action.key,
            label: t(`links.${action.key}`),
            icon: action.icon,
            href: route(action.routeName, { open: 'create' }),
        }));
    });

    return { actions };
}
