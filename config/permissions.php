<?php

/*
 | Authorization catalog: the canonical list of permissions and the role -> permission
 | mapping. Consumed by Database\Seeders\RolesAndPermissionsSeeder.
 |
 | super_admin is intentionally absent: it bypasses all checks via Gate::before.
*/

return [

    'permissions' => [
        // Platform
        'branches.view_all', 'branches.manage',
        'users.view', 'users.manage',
        'roles.manage',
        'settings.manage',
        'modules.manage',
        'audit.view',

        // Master data
        'products.view', 'products.create', 'products.update', 'products.delete',
        'categories.manage', 'brands.manage',
        'customers.view', 'customers.create', 'customers.update', 'customers.delete',
        'suppliers.view', 'suppliers.create', 'suppliers.update', 'suppliers.delete',

        // Inventory
        'inventory.view', 'inventory.adjust', 'inventory.transfer', 'inventory.receive',

        // Purchasing
        'purchases.view', 'purchases.create', 'purchases.update', 'purchases.delete', 'purchases.receive',

        // Sales
        'invoices.view', 'invoices.create', 'invoices.update', 'invoices.delete',
        'payments.view', 'payments.create',

        // Debts
        'debts.view', 'debts.manage',

        // Expenses
        'expenses.view', 'expenses.create', 'expenses.update', 'expenses.delete',

        // Shipping
        'shipping.view', 'shipping.manage',

        // Insight
        'reports.view', 'analytics.view',
    ],

    'roles' => [
        'admin' => ['*'],

        'branch_manager' => [
            'products.view', 'products.create', 'products.update',
            'categories.manage', 'brands.manage',
            'customers.view', 'customers.create', 'customers.update',
            'suppliers.view', 'suppliers.create', 'suppliers.update',
            'inventory.view', 'inventory.adjust', 'inventory.transfer', 'inventory.receive',
            'purchases.view', 'purchases.create', 'purchases.update', 'purchases.receive',
            'invoices.view', 'invoices.create', 'invoices.update',
            'payments.view', 'payments.create',
            'debts.view', 'debts.manage',
            'expenses.view', 'expenses.create', 'expenses.update',
            'shipping.view', 'shipping.manage',
            'reports.view', 'analytics.view',
            'users.view',
        ],

        'accountant' => [
            'invoices.view',
            'payments.view', 'payments.create',
            'debts.view', 'debts.manage',
            'expenses.view', 'expenses.create', 'expenses.update', 'expenses.delete',
            'reports.view', 'analytics.view',
            'customers.view', 'suppliers.view',
        ],

        'salesperson' => [
            'products.view',
            'customers.view', 'customers.create', 'customers.update',
            'invoices.view', 'invoices.create',
            'payments.view', 'payments.create',
            'inventory.view',
        ],

        'inventory_clerk' => [
            'products.view', 'products.create', 'products.update',
            'categories.manage', 'brands.manage',
            'inventory.view', 'inventory.adjust', 'inventory.transfer', 'inventory.receive',
            'purchases.view', 'purchases.receive',
            'suppliers.view',
        ],

        'viewer' => [
            'products.view', 'customers.view', 'suppliers.view',
            'inventory.view', 'purchases.view', 'invoices.view',
            'debts.view', 'expenses.view', 'shipping.view',
            'reports.view',
        ],
    ],
];
