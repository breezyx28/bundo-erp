<?php

namespace Tests\Feature\Navigation;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesInvoice;
use App\Models\Shipment;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Branch\BranchContext;
use App\Services\Inventory\InventoryService;
use App\Services\Navigation\NavBadgeService;
use App\Services\Purchasing\PurchaseService;
use App\Services\Sales\SalesService;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class NavBadgeTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Branch $branch;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ModuleSeeder::class);

        $this->tenant = Tenant::create(['name' => 'Acme']);
        $this->branch = Branch::create(['tenant_id' => $this->tenant->id, 'name' => 'Main', 'code' => 'M']);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Admin',
            'email' => 'a@a.com',
            'password' => 'x',
            'is_active' => true,
        ]);
        $this->user->assignRole('admin');
        $this->user->branches()->attach($this->branch->id);
        $this->actingAs($this->user);
        app(BranchContext::class)->flushCache();
    }

    protected function badges(): array
    {
        Cache::flush();

        return app(NavBadgeService::class)->badges($this->user);
    }

    public function test_overdue_receivable_shows_on_debts_nav_badge(): void
    {
        $customer = Customer::create(['tenant_id' => $this->tenant->id, 'name' => 'Omar', 'is_active' => true]);
        $product = Product::factory()->for($this->tenant)->create(['selling_price' => 1000]);
        app(InventoryService::class)->receive($this->branch->id, $product->id, 5, 500);

        app(SalesService::class)->createInvoice([
            'customer_id' => $customer->id,
            'invoice_date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->subDays(2)->toDateString(),
            'sale_type' => SalesInvoice::TYPE_CREDIT,
            'payment_method' => 'cash',
            'paid_amount' => 0,
        ], [
            ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 1000],
        ]);

        $badges = $this->badges();

        $this->assertSame(1, $badges['debts.index']['count']);
        $this->assertSame('error', $badges['debts.index']['tone']);
    }

    public function test_unpaid_purchase_shows_on_purchases_nav_badge(): void
    {
        $supplier = Supplier::create(['tenant_id' => $this->tenant->id, 'name' => 'Vendor', 'is_active' => true]);
        $product = Product::factory()->for($this->tenant)->create();

        app(PurchaseService::class)->save([
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
        ], [
            ['product_id' => $product->id, 'quantity' => 2, 'cost_per_unit' => 500],
        ]);

        $badges = $this->badges();

        $this->assertSame(1, $badges['purchases.index']['count']);
        $this->assertSame('warning', $badges['purchases.index']['tone']);
    }

    public function test_low_stock_shows_on_inventory_nav_badge(): void
    {
        $product = Product::factory()->for($this->tenant)->create(['reorder_level' => 10]);
        app(InventoryService::class)->receive($this->branch->id, $product->id, 3, 500);

        $badges = $this->badges();

        $this->assertSame(1, $badges['inventory.index']['count']);
        $this->assertSame('warning', $badges['inventory.index']['tone']);
    }

    public function test_unread_notifications_show_on_notifications_nav_badge(): void
    {
        $this->user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\TestAlert',
            'data' => ['message' => 'hello'],
        ]);

        $badges = $this->badges();

        $this->assertSame(1, $badges['notifications.index']['count']);
        $this->assertSame('primary', $badges['notifications.index']['tone']);
    }

    public function test_pending_shipment_shows_on_shipments_nav_badge(): void
    {
        $customer = Customer::create(['tenant_id' => $this->tenant->id, 'name' => 'Ship To', 'is_active' => true]);
        $product = Product::factory()->for($this->tenant)->create(['selling_price' => 1000]);
        app(InventoryService::class)->receive($this->branch->id, $product->id, 5, 500);

        $invoice = app(SalesService::class)->createInvoice([
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'sale_type' => SalesInvoice::TYPE_CASH,
            'payment_method' => 'cash',
        ], [
            ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 1000],
        ]);

        $company = \App\Models\LogisticsCompany::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Express',
            'is_active' => true,
        ]);

        Shipment::create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'sales_invoice_id' => $invoice->id,
            'customer_id' => $customer->id,
            'logistics_company_id' => $company->id,
            'tracking_number' => 'TRK-001',
            'dispatch_city' => 'Khartoum',
            'delivery_city' => 'Port Sudan',
            'status' => Shipment::STATUS_PENDING,
            'shipping_cost' => 100,
            'created_by' => $this->user->id,
        ]);

        $badges = $this->badges();

        $this->assertSame(1, $badges['shipments.index']['count']);
        $this->assertSame('warning', $badges['shipments.index']['tone']);
    }

    public function test_nav_badges_are_shared_with_inertia(): void
    {
        $supplier = Supplier::create(['tenant_id' => $this->tenant->id, 'name' => 'Vendor', 'is_active' => true]);
        $product = Product::factory()->for($this->tenant)->create();

        app(PurchaseService::class)->save([
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
        ], [
            ['product_id' => $product->id, 'quantity' => 1, 'cost_per_unit' => 1000],
        ]);

        Cache::flush();

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('nav', function ($nav) {
                return collect($nav)->contains(
                    fn ($item) => ($item['route'] ?? null) === 'purchases.index'
                        && ($item['badge']['count'] ?? 0) === 1,
                );
            }));
    }
}
