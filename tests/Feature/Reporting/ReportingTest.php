<?php

namespace Tests\Feature\Reporting;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Branch\BranchContext;
use App\Services\Inventory\InventoryService;
use App\Services\Reporting\DashboardService;
use App\Services\Reporting\FinancialReportService;
use App\Services\Sales\SalesService;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportingTest extends TestCase
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

    protected function makeSale(float $price = 1000, float $cost = 600, int $qty = 10): SalesInvoice
    {
        $product = Product::factory()->for($this->tenant)->create(['selling_price' => $price]);
        $this->inventory->receive($this->branch->id, $product->id, $qty + 5, $cost);

        return $this->sales->createInvoice([
            'invoice_date' => now()->toDateString(),
            'sale_type' => SalesInvoice::TYPE_CASH,
            'payment_method' => 'cash',
        ], [
            ['product_id' => $product->id, 'quantity' => $qty, 'unit_price' => $price],
        ]);
    }

    protected function makeExpense(float $amount): void
    {
        $category = ExpenseCategory::factory()->for($this->tenant)->create();
        Expense::create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'expense_category_id' => $category->id,
            'amount' => $amount,
            'description' => 'Test',
            'expense_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);
    }

    public function test_dashboard_kpis_compute_revenue_and_profit(): void
    {
        $this->makeSale(price: 1000, cost: 600, qty: 10); // revenue 10000, cogs 6000
        $this->makeExpense(1500);

        $kpis = app(DashboardService::class)->kpis();

        $this->assertSame(10000.0, $kpis['revenue']['month']);
        $this->assertSame(1500.0, $kpis['expenses']['month']);
        $this->assertSame(2500.0, $kpis['profit']['month']); // 10000 - 6000 - 1500
        $this->assertCount(1, $kpis['top_products']);
        $this->assertSame(10.0, $kpis['top_products'][0]['value']);
        $this->assertCount(12, $kpis['trend']['labels']);
    }

    public function test_profit_and_loss_statement(): void
    {
        $this->makeSale(price: 2000, cost: 800, qty: 5); // revenue 10000, cogs 4000
        $this->makeExpense(2000);

        $pnl = app(FinancialReportService::class)->profitAndLoss(now()->startOfMonth()->toDateString(), now()->toDateString());

        $this->assertSame(10000.0, $pnl['revenue']);
        $this->assertSame(4000.0, $pnl['cogs']);
        $this->assertSame(6000.0, $pnl['gross_profit']);
        $this->assertSame(2000.0, $pnl['expenses']);
        $this->assertSame(4000.0, $pnl['net_profit']);
    }

    public function test_cash_flow_statement(): void
    {
        $this->makeSale(price: 1000, cost: 500, qty: 4); // cash payment in 4000
        $this->makeExpense(1000);

        $cf = app(FinancialReportService::class)->cashFlow(now()->subDay()->toDateString(), now()->addDay()->toDateString());

        $this->assertSame(4000.0, $cf['cash_in']);
        $this->assertSame(1000.0, $cf['cash_out_expenses']);
        $this->assertSame(3000.0, $cf['net']);
    }

    public function test_branch_comparison_lists_branch_profit(): void
    {
        $this->makeSale(price: 1000, cost: 600, qty: 10);
        $this->makeExpense(1000);

        $rows = app(FinancialReportService::class)->branchComparison(now()->startOfMonth()->toDateString(), now()->toDateString());

        $this->assertCount(1, $rows);
        $this->assertSame('Main', $rows[0]['branch']);
        $this->assertSame(10000.0, $rows[0]['revenue']);
        $this->assertSame(3000.0, $rows[0]['profit']); // 10000 - 6000 - 1000
    }

    public function test_dashboard_and_reports_pages_render(): void
    {
        $this->makeSale();
        $this->get(route('dashboard'))->assertOk();
        $this->get(route('reports.index'))->assertOk()->assertSee('CSV')->assertSee('PDF');
    }

    public function test_report_exports_csv_and_pdf(): void
    {
        $this->makeSale();

        $csv = $this->get(route('reports.export', ['type' => 'pnl', 'format' => 'csv', 'from' => now()->startOfMonth()->toDateString(), 'to' => now()->toDateString()]));
        $csv->assertOk();
        $this->assertStringContainsString('text/csv', (string) $csv->headers->get('content-type'));

        $pdf = $this->get(route('reports.export', ['type' => 'branches', 'format' => 'pdf', 'from' => now()->startOfMonth()->toDateString(), 'to' => now()->toDateString()]));
        $pdf->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('content-type'));
    }
}
