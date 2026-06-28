<?php

namespace Tests\Feature\Inventory;

use App\Models\Branch;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Branch\BranchContext;
use App\Services\Inventory\InventoryService;
use App\Services\Inventory\StockTransferService;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class StockTransferTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Branch $from;

    protected Branch $to;

    protected InventoryService $inventory;

    protected StockTransferService $transfers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ModuleSeeder::class);

        $this->tenant = Tenant::create(['name' => 'Acme']);
        $this->from = Branch::create(['tenant_id' => $this->tenant->id, 'name' => 'From', 'code' => 'F']);
        $this->to = Branch::create(['tenant_id' => $this->tenant->id, 'name' => 'To', 'code' => 'T']);

        $user = User::create([
            'tenant_id' => $this->tenant->id,
            'default_branch_id' => $this->from->id,
            'name' => 'Admin',
            'email' => 'a@a.com',
            'password' => 'x',
            'is_active' => true,
        ]);
        $user->assignRole('admin');
        $user->branches()->attach([$this->from->id, $this->to->id]);
        $this->actingAs($user);
        app(BranchContext::class)->flushCache();

        $this->inventory = app(InventoryService::class);
        $this->transfers = app(StockTransferService::class);
    }

    public function test_full_lifecycle_moves_stock_between_branches(): void
    {
        $product = Product::factory()->for($this->tenant)->create();
        $this->inventory->receive($this->from->id, $product->id, 20, 800);

        $transfer = $this->transfers->request($this->from->id, $this->to->id, [
            ['product_id' => $product->id, 'quantity' => 12],
        ]);

        $this->assertSame(StockTransfer::STATUS_REQUESTED, $transfer->status);
        $this->assertNotEmpty($transfer->number);

        $this->transfers->approve($transfer);
        $this->transfers->dispatch($transfer->refresh());

        // Stock has left the source branch at dispatch.
        $this->assertSame(8, $this->inventory->availableQuantity($product->id, branchId: $this->from->id));
        $this->assertSame(0, $this->inventory->availableQuantity($product->id, branchId: $this->to->id));

        $this->transfers->receive($transfer->refresh());

        // ...and arrived at the destination at receipt, preserving cost.
        $this->assertSame(12, $this->inventory->availableQuantity($product->id, branchId: $this->to->id));
        $this->assertEqualsWithDelta(800.0, (float) $transfer->refresh()->items->first()->unit_cost, 0.01);
        $this->assertSame(StockTransfer::STATUS_RECEIVED, $transfer->status);
    }

    public function test_cannot_dispatch_before_approval(): void
    {
        $product = Product::factory()->for($this->tenant)->create();
        $this->inventory->receive($this->from->id, $product->id, 5, 100);

        $transfer = $this->transfers->request($this->from->id, $this->to->id, [
            ['product_id' => $product->id, 'quantity' => 2],
        ]);

        $this->expectException(LogicException::class);
        $this->transfers->dispatch($transfer);
    }

    public function test_same_branch_transfer_is_rejected(): void
    {
        $this->expectException(LogicException::class);
        $this->transfers->request($this->from->id, $this->from->id, [
            ['product_id' => 1, 'quantity' => 1],
        ]);
    }
}
