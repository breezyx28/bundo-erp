<?php

namespace Tests\Feature\Security;

use App\Models\Branch;
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

class BranchIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Branch $branchA;

    protected Branch $branchB;

    protected SalesService $sales;

    protected InventoryService $inventory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ModuleSeeder::class);

        $this->tenant = Tenant::create(['name' => 'Acme']);
        $this->branchA = Branch::create(['tenant_id' => $this->tenant->id, 'name' => 'Khartoum', 'code' => 'KRT']);
        $this->branchB = Branch::create(['tenant_id' => $this->tenant->id, 'name' => 'Omdurman', 'code' => 'OMD']);

        $this->sales = app(SalesService::class);
        $this->inventory = app(InventoryService::class);
    }

    protected function admin(): User
    {
        $user = User::create([
            'tenant_id' => $this->tenant->id,
            'default_branch_id' => $this->branchA->id,
            'name' => 'Admin',
            'email' => 'admin@acme.test',
            'password' => 'secret',
            'is_active' => true,
        ]);
        $user->assignRole('admin');
        $user->branches()->attach([$this->branchA->id, $this->branchB->id]);

        return $user;
    }

    /** Branch-isolated user (branch_manager lacks branches.view_all), assigned only to branch B. */
    protected function branchManagerForB(): User
    {
        $user = User::create([
            'tenant_id' => $this->tenant->id,
            'default_branch_id' => $this->branchB->id,
            'name' => 'Manager B',
            'email' => 'mgr-b@acme.test',
            'password' => 'secret',
            'is_active' => true,
        ]);
        $user->assignRole('branch_manager');
        $user->branches()->attach($this->branchB->id);

        return $user;
    }

    protected function seedInvoiceIn(Branch $branch): SalesInvoice
    {
        $context = app(BranchContext::class);

        return $context->forBranch($branch->id, function () use ($branch) {
            $product = Product::factory()->for($this->tenant)->create(['selling_price' => 1000]);
            $this->inventory->receive($branch->id, $product->id, 10, 500);

            return $this->sales->createInvoice([
                'invoice_date' => now()->toDateString(),
                'sale_type' => SalesInvoice::TYPE_CASH,
                'payment_method' => 'cash',
            ], [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 1000],
            ]);
        });
    }

    public function test_branch_user_only_sees_own_branch_transactions(): void
    {
        $this->actingAs($this->admin());
        app(BranchContext::class)->flushCache();

        $invoiceA = $this->seedInvoiceIn($this->branchA);
        $invoiceB = $this->seedInvoiceIn($this->branchB);

        // Re-authenticate as the branch-isolated manager (branch B only).
        $this->actingAs($this->branchManagerForB());
        app(BranchContext::class)->flushCache();

        $visible = SalesInvoice::query()->pluck('id');

        $this->assertTrue($visible->contains($invoiceB->id), 'Manager B must see branch B invoice');
        $this->assertFalse($visible->contains($invoiceA->id), 'Manager B must NOT see branch A invoice');
        $this->assertNull(SalesInvoice::query()->find($invoiceA->id));
    }

    public function test_consolidated_admin_sees_all_branches(): void
    {
        $this->actingAs($this->admin());
        app(BranchContext::class)->flushCache();

        $invoiceA = $this->seedInvoiceIn($this->branchA);
        $invoiceB = $this->seedInvoiceIn($this->branchB);

        $context = app(BranchContext::class);
        $context->setBranch('all');
        $context->flushCache();

        $visible = SalesInvoice::query()->pluck('id');

        $this->assertTrue($visible->contains($invoiceA->id));
        $this->assertTrue($visible->contains($invoiceB->id));
    }

    public function test_branch_user_cannot_switch_into_unassigned_branch(): void
    {
        $this->actingAs($this->branchManagerForB());
        $context = app(BranchContext::class);
        $context->flushCache();

        // Attempt to switch to branch A which the manager is not assigned to.
        $context->setBranch($this->branchA->id);

        $this->assertNotSame($this->branchA->id, $context->currentBranchId());
        $this->assertSame($this->branchB->id, $context->currentBranchId());
    }
}
