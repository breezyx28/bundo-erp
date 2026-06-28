<?php

namespace Tests\Feature\Analytics;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Analytics\AnalyticsService;
use App\Services\Branch\BranchContext;
use App\Services\Inventory\InventoryService;
use App\Services\Sales\SalesService;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Branch $branch;

    protected SalesService $sales;

    protected InventoryService $inventory;

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

        $this->sales = app(SalesService::class);
        $this->inventory = app(InventoryService::class);
    }

    protected function makeSale(string $date, float $price, float $cost, int $qty, ?int $customerId = null): SalesInvoice
    {
        $product = Product::factory()->for($this->tenant)->create(['selling_price' => $price]);
        $this->inventory->receive($this->branch->id, $product->id, $qty + 5, $cost);

        return $this->sales->createInvoice([
            'invoice_date' => $date,
            'customer_id' => $customerId,
            'sale_type' => SalesInvoice::TYPE_CASH,
            'payment_method' => 'cash',
        ], [
            ['product_id' => $product->id, 'quantity' => $qty, 'unit_price' => $price],
        ]);
    }

    public function test_sales_forecast_projects_from_trend(): void
    {
        // Rising monthly revenue over the last four months.
        $this->makeSale(now()->subMonths(3)->toDateString(), 100, 50, 10); // 1000
        $this->makeSale(now()->subMonths(2)->toDateString(), 200, 50, 10); // 2000
        $this->makeSale(now()->subMonths(1)->toDateString(), 300, 50, 10); // 3000
        $this->makeSale(now()->toDateString(), 400, 50, 10);               // 4000

        $forecast = app(AnalyticsService::class)->salesForecast();

        $this->assertCount(18, $forecast['labels']); // 12 history + 6 ahead
        $this->assertCount(18, $forecast['actual']);
        $this->assertCount(18, $forecast['forecast']);

        // Upward trend → positive growth and a non-null forward projection.
        $this->assertGreaterThan(0, $forecast['growth']);
        $this->assertNotNull($forecast['forecast'][12]);
        $this->assertGreaterThan(0.0, (float) $forecast['forecast'][12]);
    }

    public function test_product_performance_ranks_best_and_slow(): void
    {
        $fast = Product::factory()->for($this->tenant)->create(['name' => 'Fast Runner', 'selling_price' => 100]);
        $slow = Product::factory()->for($this->tenant)->create(['name' => 'Slow Mover', 'selling_price' => 100]);

        $this->inventory->receive($this->branch->id, $fast->id, 100, 40);
        $this->inventory->receive($this->branch->id, $slow->id, 50, 40);

        $this->sales->createInvoice(
            ['invoice_date' => now()->toDateString(), 'sale_type' => SalesInvoice::TYPE_CASH, 'payment_method' => 'cash'],
            [['product_id' => $fast->id, 'quantity' => 30, 'unit_price' => 100]],
        );

        $perf = app(AnalyticsService::class)->productPerformance();

        $this->assertSame('Fast Runner', $perf['best'][0]['name']);
        $this->assertSame(30.0, $perf['best'][0]['qty']);

        // The slow mover has stock but zero sales, so it leads the slow list.
        $this->assertSame('Slow Mover', $perf['slow'][0]['name']);
        $this->assertSame(0.0, $perf['slow'][0]['sold']);
    }

    public function test_customer_analysis_computes_clv_and_segment(): void
    {
        $customer = Customer::factory()->for($this->tenant)->create(['name' => 'Regular Joe']);

        $this->makeSale(now()->toDateString(), 500, 200, 4, $customer->id); // 2000 CLV

        $analysis = app(AnalyticsService::class)->customerAnalysis();

        $this->assertNotEmpty($analysis);
        $this->assertSame('Regular Joe', $analysis[0]['name']);
        $this->assertSame(2000.0, $analysis[0]['clv']);
        $this->assertSame(1, $analysis[0]['orders']);
        $this->assertSame('active', $analysis[0]['segment']);
    }

    public function test_inventory_optimization_suggests_reorder(): void
    {
        $product = Product::factory()->for($this->tenant)->create(['name' => 'Hot Item', 'selling_price' => 100]);
        $this->inventory->receive($this->branch->id, $product->id, 100, 40);

        // Heavy recent demand against limited stock → projected stockout within the window.
        $this->sales->createInvoice(
            ['invoice_date' => now()->toDateString(), 'sale_type' => SalesInvoice::TYPE_CASH, 'payment_method' => 'cash'],
            [['product_id' => $product->id, 'quantity' => 90, 'unit_price' => 100]],
        );

        $opt = app(AnalyticsService::class)->inventoryOptimization();

        $this->assertNotEmpty($opt);
        $this->assertSame('Hot Item', $opt[0]['name']);
        $this->assertSame(10.0, $opt[0]['stock']);
        $this->assertGreaterThan(0.0, $opt[0]['reorder']);
    }

    public function test_branch_ranking_orders_by_profit(): void
    {
        $this->makeSale(now()->toDateString(), 1000, 600, 5); // revenue 5000, cogs 3000

        $ranking = app(AnalyticsService::class)->branchRanking();

        $this->assertCount(1, $ranking);
        $this->assertSame(1, $ranking[0]['rank']);
        $this->assertSame('Main', $ranking[0]['branch']);
        $this->assertSame(5000.0, $ranking[0]['revenue']);
        $this->assertSame(2000.0, $ranking[0]['profit']); // 5000 - 3000 - 0 expenses
    }

    public function test_analytics_page_renders(): void
    {
        $this->makeSale(now()->toDateString(), 100, 50, 5);

        $this->get(route('analytics.index'))->assertOk()->assertSeeLivewire('analytics.index');
    }
}
