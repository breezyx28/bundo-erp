<?php

namespace Tests\Feature\MasterData;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerBranchBalance;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Branch\BranchContext;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Branch $branchA;

    protected Branch $branchB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ModuleSeeder::class);
        $this->tenant = Tenant::create(['name' => 'Acme']);
        $this->branchA = Branch::create(['tenant_id' => $this->tenant->id, 'name' => 'A', 'code' => 'A']);
        $this->branchB = Branch::create(['tenant_id' => $this->tenant->id, 'name' => 'B', 'code' => 'B']);
    }

    protected function actingAsAdmin(): User
    {
        $user = User::create([
            'tenant_id' => $this->tenant->id,
            'default_branch_id' => $this->branchA->id,
            'name' => 'Admin',
            'email' => 'a@a.com',
            'password' => 'x',
            'is_active' => true,
        ]);
        $user->assignRole('admin');
        $user->branches()->attach([$this->branchA->id, $this->branchB->id]);
        $this->actingAs($user);
        app(BranchContext::class)->flushCache();

        return $user;
    }

    public function test_quick_create_persists_customer(): void
    {
        $this->actingAsAdmin();

        $this->post(route('customers.store'), [
            'name' => 'Walk-in Ahmed',
            'phone' => '+249900000000',
            'type' => 'retail',
        ])->assertRedirect();

        $this->assertDatabaseHas('customers', ['name' => 'Walk-in Ahmed', 'tenant_id' => $this->tenant->id]);
    }

    public function test_branch_balance_resolves_per_branch_and_consolidated(): void
    {
        $this->actingAsAdmin();
        $customer = Customer::create(['tenant_id' => $this->tenant->id, 'name' => 'Debtor', 'credit_limit' => 100000]);

        CustomerBranchBalance::create(['customer_id' => $customer->id, 'branch_id' => $this->branchA->id, 'balance' => 30000]);
        CustomerBranchBalance::create(['customer_id' => $customer->id, 'branch_id' => $this->branchB->id, 'balance' => 20000]);

        // Active branch A
        app(BranchContext::class)->setBranch($this->branchA->id);
        $this->assertSame(30000.0, $customer->fresh()->currentBalance());

        // Consolidated view sums allowed branches
        app(BranchContext::class)->setBranch('all');
        $this->assertSame(50000.0, $customer->fresh()->currentBalance());
    }

    public function test_customer_badges_reflect_state(): void
    {
        $this->actingAsAdmin();
        $customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'VIP Co',
            'type' => 'wholesale',
            'credit_limit' => 10000,
        ]);
        CustomerBranchBalance::create(['customer_id' => $customer->id, 'branch_id' => $this->branchA->id, 'balance' => 50000]);

        app(BranchContext::class)->setBranch($this->branchA->id);

        $labels = collect($customer->fresh()->badges())->pluck('label');

        $this->assertContains('wholesale', $labels);
        $this->assertContains('new', $labels);
        $this->assertContains('over_limit', $labels);
    }
}
