<?php

namespace Tests\Feature\Settings;

use App\Models\Branch;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Branch\BranchContext;
use App\Services\Inventory\InventoryService;
use App\Services\Sales\SalesService;
use App\Services\Settings\SettingsManager;
use App\Support\TenantMoney;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExchangeRateTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Branch $branch;

    protected SalesService $sales;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ModuleSeeder::class);

        $this->tenant = Tenant::create(['name' => 'Acme']);
        $this->branch = Branch::create(['tenant_id' => $this->tenant->id, 'name' => 'Main', 'code' => 'M']);

        $user = User::create([
            'tenant_id' => $this->tenant->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Admin',
            'email' => 'a@a.com',
            'password' => 'x',
            'is_active' => true,
        ]);
        $user->assignRole('admin');
        $user->branches()->attach($this->branch->id);
        $this->actingAs($user);
        app(BranchContext::class)->flushCache();

        app(SettingsManager::class)->set('exchange_rate', 4900.0, group: 'currency', type: 'float');

        $this->sales = app(SalesService::class);
    }

    public function test_tenant_money_reads_saved_exchange_rate(): void
    {
        $this->assertSame(4900.0, TenantMoney::exchangeRate());
    }

    public function test_sales_page_shares_exchange_rate_globally(): void
    {
        $this->get(route('sales.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('money.exchangeRate', fn ($rate) => (float) $rate === 4900.0));
    }

    public function test_invoice_creation_falls_back_to_tenant_exchange_rate(): void
    {
        $product = Product::factory()->for($this->tenant)->create(['cost_price' => 500, 'selling_price' => 1000]);
        app(InventoryService::class)->receive($this->branch->id, $product->id, 10, 500);

        $invoice = $this->sales->createInvoice([
            'invoice_date' => now()->toDateString(),
            'sale_type' => SalesInvoice::TYPE_CASH,
            'payment_method' => 'cash',
            'exchange_rate' => 0,
        ], [
            ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 1000],
        ]);

        $this->assertSame(4900.0, (float) $invoice->exchange_rate);
    }
}
