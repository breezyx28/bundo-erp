<?php

namespace Tests\Feature\Sales;

use App\Models\Branch;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Branch\BranchContext;
use App\Services\Inventory\InventoryService;
use App\Services\Reporting\DashboardService;
use App\Services\Sales\SalesService;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesDraftTest extends TestCase
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
            'email' => 'draft@a.com',
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

    protected function stockedProduct(int $qty = 100): Product
    {
        $product = Product::factory()->for($this->tenant)->create(['selling_price' => 1500]);
        $this->inventory->receive($this->branch->id, $product->id, $qty, 1000);

        return $product;
    }

    public function test_hold_does_not_deduct_stock(): void
    {
        $product = $this->stockedProduct(20);

        $draft = $this->sales->saveDraft([
            'invoice_date' => now()->toDateString(),
            'sale_type' => SalesInvoice::TYPE_CASH,
            'hold_label' => 'Ahmed — belt',
        ], [
            ['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 1500],
        ]);

        $this->assertSame(SalesInvoice::STATUS_DRAFT, $draft->status);
        $this->assertNull($draft->invoice_number);
        $this->assertSame(20, $this->inventory->availableQuantity($product->id, branchId: $this->branch->id));
    }

    public function test_post_draft_deducts_stock_and_assigns_number(): void
    {
        $product = $this->stockedProduct(20);

        $draft = $this->sales->saveDraft([
            'invoice_date' => now()->toDateString(),
            'sale_type' => SalesInvoice::TYPE_CASH,
        ], [
            ['product_id' => $product->id, 'quantity' => 4, 'unit_price' => 1500],
        ]);

        $posted = $this->sales->postDraft($draft, ['payment_method' => 'cash']);

        $this->assertSame(SalesInvoice::STATUS_POSTED, $posted->status);
        $this->assertNotNull($posted->invoice_number);
        $this->assertSame(16, $this->inventory->availableQuantity($product->id, branchId: $this->branch->id));
        $this->assertSame(SalesInvoice::PAY_PAID, $posted->payment_status);
    }

    public function test_discard_removes_draft(): void
    {
        $product = $this->stockedProduct(10);

        $draft = $this->sales->saveDraft([
            'invoice_date' => now()->toDateString(),
            'sale_type' => SalesInvoice::TYPE_CASH,
        ], [
            ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 1000],
        ]);

        $this->sales->discardDraft($draft);

        $this->assertDatabaseMissing('sales_invoices', ['id' => $draft->id]);
    }

    public function test_drafts_excluded_from_sales_index_and_dashboard(): void
    {
        $product = $this->stockedProduct(10);

        $this->sales->saveDraft([
            'invoice_date' => now()->toDateString(),
            'sale_type' => SalesInvoice::TYPE_CASH,
        ], [
            ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 5000],
        ]);

        $this->get(route('sales.index'))->assertOk()->assertInertia(
            fn ($page) => $page->has('drafts', 1)->where('invoices.total', 0),
        );

        app(DashboardService::class)->refresh();
        $kpis = app(DashboardService::class)->kpis();
        $this->assertSame(0.0, $kpis['revenue']['today']);
    }

    public function test_posted_invoice_appears_in_sales_table(): void
    {
        $product = $this->stockedProduct(10);

        $this->sales->createInvoice([
            'invoice_date' => now()->toDateString(),
            'sale_type' => SalesInvoice::TYPE_CASH,
            'payment_method' => 'cash',
        ], [
            ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 1000],
        ]);

        $this->get(route('sales.index'))->assertOk()->assertInertia(
            fn ($page) => $page->where('invoices.total', 1),
        );
    }
}
