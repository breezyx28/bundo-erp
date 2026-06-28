<?php

namespace Tests\Feature\Sales;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerBranchBalance;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Branch\BranchContext;
use App\Services\Inventory\InventoryService;
use App\Services\Sales\SalesService;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesInvoiceTest extends TestCase
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

    protected function stockedProduct(int $qty = 100, float $cost = 1000, float $price = 1500): Product
    {
        $product = Product::factory()->for($this->tenant)->create(['cost_price' => $cost, 'selling_price' => $price]);
        $this->inventory->receive($this->branch->id, $product->id, $qty, $cost);

        return $product;
    }

    public function test_cash_sale_deducts_stock_captures_cogs_and_is_paid(): void
    {
        $product = $this->stockedProduct(50, 1000, 1500);

        $invoice = $this->sales->createInvoice([
            'invoice_date' => now()->toDateString(),
            'sale_type' => SalesInvoice::TYPE_CASH,
            'payment_method' => 'cash',
            'exchange_rate' => 600,
        ], [
            ['product_id' => $product->id, 'quantity' => 10, 'unit_price' => 1500],
        ]);

        $this->assertSame('15000.00', $invoice->net_amount);
        $this->assertSame('10000.00', $invoice->cost_total);
        $this->assertSame(SalesInvoice::PAY_PAID, $invoice->payment_status);
        $this->assertSame(0.0, (float) $invoice->balance);
        $this->assertSame(40, $this->inventory->availableQuantity($product->id, branchId: $this->branch->id));
        $this->assertSame(5000.0, $invoice->profit());
        $this->assertDatabaseHas('payments', ['sales_invoice_id' => $invoice->id, 'direction' => 'in', 'amount' => 15000]);
    }

    public function test_fifo_cogs_spans_multiple_batches(): void
    {
        $product = Product::factory()->for($this->tenant)->create(['selling_price' => 2000]);
        $this->inventory->receive($this->branch->id, $product->id, 5, 100, receivedAt: now()->subDays(2));
        $this->inventory->receive($this->branch->id, $product->id, 5, 300, receivedAt: now()->subDay());

        $invoice = $this->sales->createInvoice([
            'invoice_date' => now()->toDateString(),
            'sale_type' => SalesInvoice::TYPE_CASH,
            'payment_method' => 'cash',
        ], [
            ['product_id' => $product->id, 'quantity' => 8, 'unit_price' => 2000],
        ]);

        // 5 @100 + 3 @300 = 1400 COGS, split across two batch-backed items.
        $this->assertSame('1400.00', $invoice->cost_total);
        $this->assertCount(2, $invoice->items);
    }

    public function test_credit_sale_raises_receivable_and_payment_reduces_it(): void
    {
        $customer = Customer::create(['tenant_id' => $this->tenant->id, 'name' => 'Omar', 'is_active' => true]);
        $product = $this->stockedProduct(20, 500, 1000);

        $invoice = $this->sales->createInvoice([
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'sale_type' => SalesInvoice::TYPE_CREDIT,
            'payment_method' => 'cash',
            'paid_amount' => 0,
        ], [
            ['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 1000],
        ]);

        $this->assertSame(SalesInvoice::PAY_UNPAID, $invoice->payment_status);
        $this->assertSame(5000.0, (float) $invoice->balance);
        $this->assertSame(5000.0, (float) CustomerBranchBalance::where('customer_id', $customer->id)->value('balance'));

        $this->sales->recordPayment($invoice, ['amount' => 2000, 'payment_method' => 'cash', 'payment_date' => now()->toDateString()]);

        $invoice->refresh();
        $this->assertSame(SalesInvoice::PAY_PARTIAL, $invoice->payment_status);
        $this->assertSame(3000.0, (float) $invoice->balance);
        $this->assertSame(3000.0, (float) CustomerBranchBalance::where('customer_id', $customer->id)->value('balance'));
    }

    public function test_header_discount_reduces_net(): void
    {
        $product = $this->stockedProduct(20, 500, 1000);

        $invoice = $this->sales->createInvoice([
            'invoice_date' => now()->toDateString(),
            'sale_type' => SalesInvoice::TYPE_CASH,
            'payment_method' => 'cash',
            'discount_type' => 'percentage',
            'discount_value' => 10,
        ], [
            ['product_id' => $product->id, 'quantity' => 4, 'unit_price' => 1000],
        ]);

        $this->assertSame('4000.00', $invoice->total_amount);
        $this->assertSame('400.00', $invoice->discount_amount);
        $this->assertSame('3600.00', $invoice->net_amount);
    }

    public function test_invoice_documents_are_reachable(): void
    {
        $product = $this->stockedProduct(10, 500, 1000);
        $invoice = $this->sales->createInvoice([
            'invoice_date' => now()->toDateString(),
            'sale_type' => SalesInvoice::TYPE_CASH,
            'payment_method' => 'cash',
        ], [
            ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 1000],
        ]);

        $this->get(route('invoices.print', $invoice->id))->assertOk()->assertSee($invoice->invoice_number);

        $pdf = $this->get(route('invoices.pdf', $invoice->id));
        $pdf->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('content-type'));
    }

    public function test_void_restores_stock_and_reverses_receivable(): void
    {
        $customer = Customer::create(['tenant_id' => $this->tenant->id, 'name' => 'Sara', 'is_active' => true]);
        $product = $this->stockedProduct(10, 500, 1000);

        $invoice = $this->sales->createInvoice([
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'sale_type' => SalesInvoice::TYPE_CREDIT,
            'payment_method' => 'cash',
            'due_date' => now()->addDays(15)->toDateString(),
        ], [
            ['product_id' => $product->id, 'quantity' => 6, 'unit_price' => 1000],
        ]);

        $this->assertSame(4, $this->inventory->availableQuantity($product->id, branchId: $this->branch->id));

        $this->sales->void($invoice);

        $this->assertSame(10, $this->inventory->availableQuantity($product->id, branchId: $this->branch->id));
        $this->assertSame(0.0, (float) CustomerBranchBalance::where('customer_id', $customer->id)->value('balance'));
        $this->assertSoftDeleted('sales_invoices', ['id' => $invoice->id]);
    }
}
