<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerBranchBalance;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\LogisticsCompany;
use App\Models\Product;
use App\Models\StockLocation;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Inventory\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            ModuleSeeder::class,
        ]);

        $tenant = Tenant::firstOrCreate(
            ['name' => 'Mazin Shoes'],
            [
                'primary_color' => '#39C6A0',
                'secondary_color' => '#228C70',
                'is_active' => true,
                'onboarding_completed_at' => now(),
            ],
        );

        if (! $tenant->onboarding_completed_at) {
            $tenant->update(['onboarding_completed_at' => now()]);
        }

        $main = Branch::firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'KRT'],
            ['name' => 'Khartoum (Main)', 'phone' => '+249100000000', 'is_active' => true],
        );

        $second = Branch::firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'OMD'],
            ['name' => 'Omdurman', 'phone' => '+249100000001', 'is_active' => true],
        );

        $admin = User::firstOrCreate(
            ['email' => 'admin@mazinshoes.com'],
            [
                'tenant_id' => $tenant->id,
                'default_branch_id' => $main->id,
                'name' => 'Mazin Admin',
                'phone' => '+249912345678',
                'password' => Hash::make('password'),
                'is_active' => true,
                'settings' => ['locale' => 'ar'],
            ],
        );

        $admin->assignRole('admin');
        $admin->branches()->syncWithoutDetaching([
            $main->id => ['is_primary' => true],
            $second->id => ['is_primary' => false],
        ]);

        User::firstOrCreate(
            ['email' => 'super@mazinshoes.com'],
            [
                'tenant_id' => null,
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'is_active' => true,
                'settings' => ['locale' => 'en'],
            ],
        )->assignRole('super_admin');

        $tenant->branches->each(function (Branch $branch) use ($admin) {
            $branch->update(['manager_id' => $branch->manager_id ?? $admin->id]);

            StockLocation::firstOrCreate(
                ['branch_id' => $branch->id, 'code' => 'MAIN'],
                ['name' => 'Main Store', 'type' => 'store', 'is_default' => true, 'is_active' => true],
            );
        });

        foreach (['Rent', 'Utilities', 'Salaries', 'Shipping & Logistics', 'Marketing', 'Maintenance'] as $name) {
            ExpenseCategory::firstOrCreate(
                ['tenant_id' => $tenant->id, 'name' => $name],
                ['is_operational' => $name !== 'Marketing', 'is_active' => true],
            );
        }

        foreach (['Sudan Express Logistics', 'Bashayer Cargo', 'Nile Couriers'] as $name) {
            LogisticsCompany::firstOrCreate(
                ['tenant_id' => $tenant->id, 'name' => $name],
                ['rating' => rand(3, 5), 'is_active' => true],
            );
        }

        if (app()->environment('local')) {
            $this->seedDemoData($tenant, $main);
        }
    }

    protected function seedDemoData(Tenant $tenant, Branch $branch): void
    {
        if (Product::withoutGlobalScopes()->where('tenant_id', $tenant->id)->exists()) {
            return;
        }

        $categories = collect(['Sneakers', 'Formal', 'Sandals', 'Boots'])
            ->map(fn ($name) => Category::create([
                'tenant_id' => $tenant->id,
                'name' => $name,
                'slug' => Str::slug($name),
            ]));

        $brands = collect(['Bata', 'Clarks', 'Nike', 'Local Craft'])
            ->map(fn ($name) => Brand::create([
                'tenant_id' => $tenant->id,
                'name' => $name,
                'slug' => Str::slug($name),
            ]));

        $inventory = app(InventoryService::class);

        foreach (range(1, 12) as $i) {
            $cost = rand(3000, 25000);
            $product = Product::create([
                'tenant_id' => $tenant->id,
                'category_id' => $categories->random()->id,
                'brand_id' => $brands->random()->id,
                'name' => 'Shoe Model '.$i,
                'sku' => 'MZN-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'unit' => 'pair',
                'cost_price' => $cost,
                'selling_price' => round($cost * 1.45, 2),
                'reorder_level' => 5,
                'is_active' => true,
            ]);

            // Seed opening stock so inventory screens have data (some intentionally low).
            $inventory->receive(
                branchId: $branch->id,
                productId: $product->id,
                quantity: $i % 4 === 0 ? rand(1, 4) : rand(20, 80),
                unitCost: (float) $cost,
            );
        }

        foreach (['Gezira Leather', 'Khartoum Imports', 'Red Sea Traders'] as $name) {
            Supplier::create([
                'tenant_id' => $tenant->id,
                'name' => $name,
                'phone' => '+2499'.rand(10000000, 99999999),
                'is_active' => true,
            ]);
        }

        foreach (['Ahmed Ali', 'Fatima Hassan', 'Omar Trading Co', 'Sara Ibrahim'] as $idx => $name) {
            $customer = Customer::create([
                'tenant_id' => $tenant->id,
                'name' => $name,
                'phone' => '+2499'.rand(10000000, 99999999),
                'type' => $idx === 2 ? 'wholesale' : 'retail',
                'credit_limit' => $idx === 2 ? 500000 : 50000,
                'is_active' => true,
            ]);

            if ($idx < 2) {
                CustomerBranchBalance::create([
                    'customer_id' => $customer->id,
                    'branch_id' => $branch->id,
                    'balance' => rand(10000, 80000),
                ]);
            }
        }

        $categories = ExpenseCategory::withoutGlobalScopes()->where('tenant_id', $tenant->id)->get();

        foreach (range(1, 8) as $i) {
            Expense::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'expense_category_id' => $categories->random()->id,
                'amount' => rand(2000, 40000),
                'description' => 'Demo expense '.$i,
                'expense_date' => now()->subDays(rand(0, 25))->toDateString(),
                'payment_method' => 'cash',
            ]);
        }
    }
}
