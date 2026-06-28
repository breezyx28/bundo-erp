<?php

namespace Tests\Feature\Expenses;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Branch\BranchContext;
use App\Services\Expenses\ExpenseService;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Branch $branch;

    protected ExpenseCategory $rent;

    protected ExpenseCategory $utilities;

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

        $this->rent = ExpenseCategory::factory()->for($this->tenant)->create(['name' => 'Rent']);
        $this->utilities = ExpenseCategory::factory()->for($this->tenant)->create(['name' => 'Utilities']);
    }

    protected function expense(ExpenseCategory $category, float $amount, ?string $date = null): Expense
    {
        return Expense::create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'expense_category_id' => $category->id,
            'amount' => $amount,
            'description' => 'Test',
            'expense_date' => $date ?? now()->toDateString(),
            'payment_method' => 'cash',
        ]);
    }

    public function test_report_groups_totals_by_category(): void
    {
        $this->expense($this->rent, 10000);
        $this->expense($this->utilities, 3000);
        $this->expense($this->utilities, 2000);

        $report = app(ExpenseService::class)->report(now()->subDay()->toDateString(), now()->addDay()->toDateString());

        $this->assertSame(15000.0, $report['total']);
        $this->assertSame(3, $report['count']);
        $this->assertSame('Rent', $report['by_category'][0]['category']);
        $this->assertSame(10000.0, $report['by_category'][0]['total']);
        $this->assertSame(5000.0, collect($report['by_category'])->firstWhere('category', 'Utilities')['total']);
    }

    public function test_report_excludes_out_of_range_dates(): void
    {
        $this->expense($this->rent, 10000, now()->subDays(60)->toDateString());

        $report = app(ExpenseService::class)->report(now()->startOfMonth()->toDateString(), now()->toDateString());

        $this->assertSame(0.0, $report['total']);
    }

    public function test_admin_can_create_an_expense_via_component(): void
    {
        Livewire::test('expenses.index')
            ->call('create')
            ->set('expense_category_id', $this->rent->id)
            ->set('amount', 7500)
            ->set('description', 'Monthly rent')
            ->set('expense_date', now()->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('expenses', [
            'expense_category_id' => $this->rent->id,
            'amount' => 7500,
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_linked_expense_records_reference(): void
    {
        Livewire::test('expenses.index')
            ->call('create')
            ->set('expense_category_id', $this->rent->id)
            ->set('amount', 1000)
            ->set('description', 'Linked')
            ->set('expense_date', now()->toDateString())
            ->set('linked', true)
            ->set('purchase_order_id', null)
            ->call('save')
            ->assertHasErrors('purchase_order_id'); // required when linked
    }
}
