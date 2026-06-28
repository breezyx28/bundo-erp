<?php

namespace Tests\Feature\Purchasing;

use App\Models\Branch;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Branch\BranchContext;
use App\Services\Inventory\InventoryService;
use App\Services\Purchasing\PurchaseService;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class PurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Branch $branch;

    protected Supplier $supplier;

    protected PurchaseService $service;

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

        $this->supplier = Supplier::create(['tenant_id' => $this->tenant->id, 'name' => 'Gezira Leather', 'is_active' => true]);
        $this->service = app(PurchaseService::class);
        $this->inventory = app(InventoryService::class);
    }

    protected function draftOrder(Product $product, int $qty = 10, float $cost = 1000): PurchaseOrder
    {
        return $this->service->save([
            'supplier_id' => $this->supplier->id,
            'order_date' => now()->toDateString(),
        ], [
            ['product_id' => $product->id, 'quantity' => $qty, 'cost_per_unit' => $cost],
        ]);
    }

    public function test_saving_computes_totals_and_assigns_a_number(): void
    {
        $product = Product::factory()->for($this->tenant)->create();
        $order = $this->draftOrder($product, 10, 1500);

        $this->assertNotEmpty($order->po_number);
        $this->assertSame('15000.00', $order->total_amount);
        $this->assertSame(PurchaseOrder::STATUS_DRAFT, $order->order_status);
        $this->assertSame($this->branch->id, $order->branch_id);
    }

    public function test_receiving_creates_stock_and_marks_received(): void
    {
        $product = Product::factory()->for($this->tenant)->create();
        $order = $this->draftOrder($product, 10, 1200);
        $this->service->place($order);

        $this->service->receive($order->refresh());

        $this->assertSame(10, $this->inventory->availableQuantity($product->id, branchId: $this->branch->id));
        $this->assertSame(PurchaseOrder::STATUS_RECEIVED, $order->refresh()->order_status);
        $this->assertSame(10, $order->items->first()->received_quantity);
    }

    public function test_partial_receipt_sets_partial_status(): void
    {
        $product = Product::factory()->for($this->tenant)->create();
        $order = $this->draftOrder($product, 10, 1200);
        $this->service->place($order);

        $itemId = $order->refresh()->items->first()->id;
        $this->service->receive($order, [$itemId => 4]);

        $this->assertSame(4, $this->inventory->availableQuantity($product->id, branchId: $this->branch->id));
        $this->assertSame(PurchaseOrder::STATUS_PARTIAL, $order->refresh()->order_status);

        // Receiving the remainder completes the order.
        $this->service->receive($order, [$itemId => 6]);
        $this->assertSame(10, $this->inventory->availableQuantity($product->id, branchId: $this->branch->id));
        $this->assertSame(PurchaseOrder::STATUS_RECEIVED, $order->refresh()->order_status);
    }

    public function test_payments_update_paid_amount_and_status(): void
    {
        $product = Product::factory()->for($this->tenant)->create();
        $order = $this->draftOrder($product, 10, 1000); // total 10,000

        $this->service->recordPayment($order, [
            'amount' => 4000,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
        ]);
        $this->assertSame(PurchaseOrder::PAY_PARTIAL, $order->refresh()->payment_status);
        $this->assertSame(6000.0, $order->outstanding());

        $this->service->recordPayment($order, [
            'amount' => 6000,
            'payment_method' => 'bank_transfer',
            'payment_date' => now()->toDateString(),
        ]);
        $this->assertSame(PurchaseOrder::PAY_PAID, $order->refresh()->payment_status);
        $this->assertSame(0.0, $order->outstanding());
    }

    public function test_cannot_cancel_after_receiving_stock(): void
    {
        $product = Product::factory()->for($this->tenant)->create();
        $order = $this->draftOrder($product, 5, 1000);
        $this->service->place($order);
        $this->service->receive($order->refresh());

        $this->expectException(LogicException::class);
        $this->service->cancel($order->refresh());
    }
}
