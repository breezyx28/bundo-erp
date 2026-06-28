<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            ['key' => 'products', 'name' => 'Products', 'icon' => 'cube', 'is_core' => true, 'sort_order' => 10],
            ['key' => 'inventory', 'name' => 'Inventory', 'icon' => 'archive-box', 'is_core' => true, 'sort_order' => 20],
            ['key' => 'customers', 'name' => 'Customers', 'icon' => 'users', 'is_core' => true, 'sort_order' => 30],
            ['key' => 'purchases', 'name' => 'Purchasing', 'icon' => 'truck', 'is_core' => true, 'sort_order' => 40],
            ['key' => 'sales', 'name' => 'Sales', 'icon' => 'shopping-cart', 'is_core' => true, 'sort_order' => 50],
            ['key' => 'debts', 'name' => 'Debts & Collections', 'icon' => 'credit-card', 'is_core' => false, 'sort_order' => 60],
            ['key' => 'expenses', 'name' => 'Expenses', 'icon' => 'banknotes', 'is_core' => false, 'sort_order' => 70],
            ['key' => 'shipping', 'name' => 'Shipping & Logistics', 'icon' => 'paper-airplane', 'is_core' => false, 'sort_order' => 80],
            ['key' => 'reports', 'name' => 'Reports', 'icon' => 'chart-bar', 'is_core' => false, 'sort_order' => 90],
            ['key' => 'analytics', 'name' => 'Analytics', 'icon' => 'presentation-chart-line', 'is_core' => false, 'sort_order' => 100],
        ];

        foreach ($modules as $module) {
            Module::updateOrCreate(['key' => $module['key']], $module + ['default_enabled' => true]);
        }
    }
}
