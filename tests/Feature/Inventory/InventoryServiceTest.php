<?php

namespace Tests\Feature\Inventory;

use App\Exceptions\InsufficientStockException;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Branch\BranchContext;
use App\Services\Inventory\InventoryService;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Branch $branch;

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

        $this->inventory = app(InventoryService::class);
    }

    protected function product(float $cost = 1000): Product
    {
        return Product::factory()->for($this->tenant)->create(['cost_price' => $cost]);
    }

    public function test_receiving_stock_creates_a_batch_and_movement(): void
    {
        $product = $this->product();

        $this->inventory->receive($this->branch->id, $product->id, 10, 1200);

        $this->assertSame(10, $this->inventory->availableQuantity($product->id, branchId: $this->branch->id));
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovement::TYPE_RECEIPT,
            'quantity_change' => 10,
        ]);
    }

    public function test_deduction_follows_fifo_and_computes_cogs(): void
    {
        $product = $this->product();

        // Oldest batch first (cheaper), then newer (pricier).
        $this->inventory->receive($this->branch->id, $product->id, 5, 100, receivedAt: now()->subDays(2));
        $this->inventory->receive($this->branch->id, $product->id, 5, 200, receivedAt: now()->subDay());

        $result = $this->inventory->deduct($this->branch->id, $product->id, 7);

        // 5 @100 + 2 @200 = 900
        $this->assertSame(900.0, $result['cost']);
        $this->assertSame(3, $this->inventory->availableQuantity($product->id, branchId: $this->branch->id));
    }

    public function test_deduction_beyond_available_throws(): void
    {
        $product = $this->product();
        $this->inventory->receive($this->branch->id, $product->id, 3, 100);

        $this->expectException(InsufficientStockException::class);
        $this->inventory->deduct($this->branch->id, $product->id, 4);
    }

    public function test_adjust_sets_absolute_quantity_in_both_directions(): void
    {
        $product = $this->product();
        $this->inventory->receive($this->branch->id, $product->id, 10, 500);

        $this->inventory->adjust($this->branch->id, $product->id, 4, reason: 'count');
        $this->assertSame(4, $this->inventory->availableQuantity($product->id, branchId: $this->branch->id));

        $this->inventory->adjust($this->branch->id, $product->id, 9, reason: 'found');
        $this->assertSame(9, $this->inventory->availableQuantity($product->id, branchId: $this->branch->id));
    }
}
