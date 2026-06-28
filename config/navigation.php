<?php

/*
 | Application navigation.
 |
 | Each item may declare:
 |   - label:      translation key
 |   - icon:       heroicon name (outline)
 |   - route:      named route (null = not yet available)
 |   - module:     module key required to be enabled (null = always)
 |   - permission: permission required (null = always)
 |
 | The sidebar renders only items whose module is enabled AND whose permission
 | the current user holds. See App\Support\Navigation.
*/

return [
    [
        'label' => 'nav.dashboard',
        'icon' => 'home',
        'route' => 'dashboard',
        'module' => null,
        'permission' => null,
    ],
    [
        'label' => 'nav.sales',
        'icon' => 'shopping-cart',
        'route' => 'sales.index',
        'module' => 'sales',
        'permission' => 'invoices.view',
    ],
    [
        'label' => 'nav.products',
        'icon' => 'cube',
        'route' => 'products.index',
        'module' => 'products',
        'permission' => 'products.view',
    ],
    [
        'label' => 'nav.categories',
        'icon' => 'tag',
        'route' => 'categories.index',
        'module' => 'products',
        'permission' => 'categories.manage',
    ],
    [
        'label' => 'nav.brands',
        'icon' => 'bookmark',
        'route' => 'brands.index',
        'module' => 'products',
        'permission' => 'brands.manage',
    ],
    [
        'label' => 'nav.inventory',
        'icon' => 'archive-box',
        'route' => 'inventory.index',
        'module' => 'inventory',
        'permission' => 'inventory.view',
    ],
    [
        'label' => 'nav.stock_transfers',
        'icon' => 'arrows-right-left',
        'route' => 'transfers.index',
        'module' => 'inventory',
        'permission' => 'inventory.view',
    ],
    [
        'label' => 'nav.customers',
        'icon' => 'users',
        'route' => 'customers.index',
        'module' => 'customers',
        'permission' => 'customers.view',
    ],
    [
        'label' => 'nav.suppliers',
        'icon' => 'truck',
        'route' => 'suppliers.index',
        'module' => 'purchases',
        'permission' => 'suppliers.view',
    ],
    [
        'label' => 'nav.purchases',
        'icon' => 'shopping-bag',
        'route' => 'purchases.index',
        'module' => 'purchases',
        'permission' => 'purchases.view',
    ],
    [
        'label' => 'nav.expenses',
        'icon' => 'banknotes',
        'route' => 'expenses.index',
        'module' => 'expenses',
        'permission' => 'expenses.view',
    ],
    [
        'label' => 'nav.debts',
        'icon' => 'credit-card',
        'route' => 'debts.index',
        'module' => 'debts',
        'permission' => 'debts.view',
    ],
    [
        'label' => 'nav.shipping',
        'icon' => 'paper-airplane',
        'route' => 'shipments.index',
        'module' => 'shipping',
        'permission' => 'shipping.view',
    ],
    [
        'label' => 'nav.logistics',
        'icon' => 'truck',
        'route' => 'logistics.index',
        'module' => 'shipping',
        'permission' => 'shipping.view',
    ],
    [
        'label' => 'nav.reports',
        'icon' => 'chart-bar',
        'route' => 'reports.index',
        'module' => 'reports',
        'permission' => 'reports.view',
    ],
    [
        'label' => 'nav.analytics',
        'icon' => 'presentation-chart-line',
        'route' => 'analytics.index',
        'module' => 'analytics',
        'permission' => 'analytics.view',
    ],
    [
        'label' => 'nav.branches',
        'icon' => 'building-storefront',
        'route' => 'branches.index',
        'module' => null,
        'permission' => 'branches.manage',
    ],
    [
        'label' => 'nav.data_tools',
        'icon' => 'circle-stack',
        'route' => 'data-tools.index',
        'module' => null,
        'permission' => 'settings.manage',
    ],
    [
        'label' => 'nav.users',
        'icon' => 'user-group',
        'route' => 'users.index',
        'module' => null,
        'permission' => 'users.manage',
    ],
    [
        'label' => 'nav.settings',
        'icon' => 'cog-6-tooth',
        'route' => 'settings.index',
        'module' => null,
        'permission' => 'settings.manage',
    ],
];
