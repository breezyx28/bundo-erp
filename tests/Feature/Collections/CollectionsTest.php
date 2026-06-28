<?php

namespace Tests\Feature\Collections;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerBranchBalance;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Branch\BranchContext;
use App\Services\Collections\CollectionsService;
use App\Services\Inventory\InventoryService;
use App\Services\Sales\SalesService;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionsTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Branch $branch;

    protected SalesService $sales;

    protected CollectionsService $collections;

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
        $this->collections = app(CollectionsService::class);
        $this->inventory = app(InventoryService::class);
    }

    protected function creditInvoice(Customer $customer, float $amount, ?string $dueDate): SalesInvoice
    {
        $product = Product::factory()->for($this->tenant)->create(['selling_price' => $amount]);
        $this->inventory->receive($this->branch->id, $product->id, 1, $amount * 0.5);

        return $this->sales->createInvoice([
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => $dueDate,
            'sale_type' => SalesInvoice::TYPE_CREDIT,
            'payment_method' => 'cash',
        ], [
            ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => $amount],
        ]);
    }

    public function test_aging_buckets_classify_by_days_overdue(): void
    {
        $customer = Customer::create(['tenant_id' => $this->tenant->id, 'name' => 'Omar', 'is_active' => true]);

        $this->creditInvoice($customer, 1000, now()->addDays(10)->toDateString());  // current
        $this->creditInvoice($customer, 2000, now()->subDays(45)->toDateString());  // 30-60
        $this->creditInvoice($customer, 4000, now()->subDays(120)->toDateString()); // 90+

        $summary = $this->collections->summary();

        $this->assertSame(1000.0, $summary['current']);
        $this->assertSame(2000.0, $summary['d30']);
        $this->assertSame(0.0, $summary['d60']);
        $this->assertSame(4000.0, $summary['d90']);
        $this->assertSame(7000.0, $summary['total']);
    }

    public function test_lump_sum_collection_applies_to_oldest_first(): void
    {
        $customer = Customer::create(['tenant_id' => $this->tenant->id, 'name' => 'Sara', 'is_active' => true]);

        $oldest = $this->creditInvoice($customer, 3000, now()->subDays(90)->toDateString());
        $newest = $this->creditInvoice($customer, 3000, now()->addDays(5)->toDateString());

        // Pay 4000: clears the oldest (3000) then 1000 toward the newest.
        $allocations = $this->collections->collectFromCustomer($customer->id, [
            'amount' => 4000,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
        ]);

        $this->assertCount(2, $allocations);
        $this->assertSame(0.0, (float) $oldest->refresh()->balance);
        $this->assertSame(2000.0, (float) $newest->refresh()->balance);
        $this->assertSame(2000.0, (float) CustomerBranchBalance::where('customer_id', $customer->id)->value('balance'));
    }

    public function test_aging_lists_customers_sorted_by_outstanding(): void
    {
        $small = Customer::create(['tenant_id' => $this->tenant->id, 'name' => 'Small', 'is_active' => true]);
        $big = Customer::create(['tenant_id' => $this->tenant->id, 'name' => 'Big', 'is_active' => true]);

        $this->creditInvoice($small, 1000, now()->addDays(5)->toDateString());
        $this->creditInvoice($big, 9000, now()->subDays(40)->toDateString());

        $aging = $this->collections->aging();

        $this->assertSame('Big', $aging[0]['customer']);
        $this->assertSame(9000.0, $aging[0]['total']);
    }

    public function test_collections_page_renders(): void
    {
        $customer = Customer::create(['tenant_id' => $this->tenant->id, 'name' => 'Omar', 'is_active' => true]);
        $this->creditInvoice($customer, 1500, now()->subDays(40)->toDateString());

        $this->get(route('debts.index'))->assertOk()->assertSee('Omar');
    }

    public function test_performance_aggregates_collected_payments(): void
    {
        $customer = Customer::create(['tenant_id' => $this->tenant->id, 'name' => 'Omar', 'is_active' => true]);
        $invoice = $this->creditInvoice($customer, 5000, now()->addDays(10)->toDateString());

        $this->sales->recordPayment($invoice, ['amount' => 2000, 'payment_method' => 'cash', 'payment_date' => now()->toDateString()]);
        $this->sales->recordPayment($invoice, ['amount' => 1000, 'payment_method' => 'bank_transfer', 'payment_date' => now()->toDateString()]);

        $performance = $this->collections->performance(now()->subDay()->toDateString(), now()->addDay()->toDateString());

        $this->assertSame(3000.0, $performance['total']);
        $this->assertSame(2, $performance['count']);
        $this->assertSame(2000.0, $performance['by_method']['cash']);
        $this->assertSame(1000.0, $performance['by_method']['bank_transfer']);
    }
}
