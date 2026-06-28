<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Branch\BranchContext;
use App\Services\Documents\DocumentNumberService;
use App\Services\Modules\ModuleManager;
use App\Services\Settings\SettingsManager;
use App\Support\Navigation;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoreInfraTest extends TestCase
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

    public function test_settings_resolve_branch_over_tenant(): void
    {
        $this->actingAsAdmin();
        $settings = app(SettingsManager::class);

        $settings->set('currency', 'SDG');
        $settings->set('currency', 'USD', branchId: $this->branchA->id);

        $this->assertSame('USD', $settings->get('currency', branchId: $this->branchA->id));
        $this->assertSame('SDG', $settings->get('currency', branchId: $this->branchB->id));
        $this->assertSame('EUR', $settings->get('missing', 'EUR'));
    }

    public function test_document_numbers_are_sequential_and_branch_scoped(): void
    {
        $this->actingAsAdmin();
        $docs = app(DocumentNumberService::class);

        $first = $docs->next('invoice', $this->branchA->id);
        $second = $docs->next('invoice', $this->branchA->id);
        $otherBranch = $docs->next('invoice', $this->branchB->id);

        $this->assertSame('INV-00001', $first);
        $this->assertSame('INV-00002', $second);
        $this->assertSame('INV-00001', $otherBranch);
    }

    public function test_admin_can_view_all_branches(): void
    {
        $this->actingAsAdmin();

        $this->assertTrue(app(BranchContext::class)->canViewAllBranches());
        $this->assertEqualsCanonicalizing(
            [$this->branchA->id, $this->branchB->id],
            app(BranchContext::class)->allowedBranchIds()->all(),
        );
    }

    public function test_module_manager_reports_enabled_modules(): void
    {
        $this->actingAsAdmin();

        $this->assertTrue(app(ModuleManager::class)->isEnabled('sales'));
        $this->assertFalse(app(ModuleManager::class)->isEnabled('nonexistent'));
    }

    public function test_authenticated_dashboard_renders(): void
    {
        $this->actingAsAdmin();

        $this->get('/')->assertOk();
    }

    public function test_navigation_is_filtered_by_permission(): void
    {
        $user = User::create([
            'tenant_id' => $this->tenant->id,
            'default_branch_id' => $this->branchA->id,
            'name' => 'Sales',
            'email' => 's@s.com',
            'password' => 'x',
            'is_active' => true,
        ]);
        $user->assignRole('salesperson');
        $user->branches()->attach($this->branchA->id);
        $this->actingAs($user);
        app(BranchContext::class)->flushCache();

        $labels = app(Navigation::class)->menu()->pluck('label')->all();

        $this->assertContains('nav.sales', $labels);
        $this->assertContains('nav.dashboard', $labels);
        $this->assertNotContains('nav.settings', $labels);
        $this->assertNotContains('nav.users', $labels);
    }
}
