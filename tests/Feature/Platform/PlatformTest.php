<?php

namespace Tests\Feature\Platform;

use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Branch\BranchContext;
use App\Services\Tenancy\TenantContext;
use App\Services\Tenancy\TenantProvisioningService;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ModuleSeeder::class);

        $this->superAdmin = User::create([
            'tenant_id' => null,
            'name' => 'Super',
            'email' => 'super@test.com',
            'password' => 'secret',
            'is_active' => true,
        ]);
        $this->superAdmin->assignRole('super_admin');
    }

    public function test_super_admin_can_view_platform_dashboard(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('platform.dashboard'))
            ->assertOk();
    }

    public function test_regular_admin_cannot_access_platform(): void
    {
        $tenant = Tenant::create(['name' => 'Acme', 'is_active' => true, 'onboarding_completed_at' => now()]);
        $branch = Branch::create(['tenant_id' => $tenant->id, 'name' => 'Main', 'code' => 'M']);
        $admin = User::create([
            'tenant_id' => $tenant->id,
            'default_branch_id' => $branch->id,
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => 'x',
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('platform.dashboard'))
            ->assertForbidden();
    }

    public function test_provisioning_creates_tenant_branch_and_admin(): void
    {
        $this->actingAs($this->superAdmin);

        $tenant = app(TenantProvisioningService::class)->create([
            'name' => 'New Co',
            'domain' => 'newco.test',
            'locale' => 'en',
            'modules' => ['sales' => true, 'analytics' => false],
            'branch' => ['name' => 'HQ', 'code' => 'HQ'],
            'admin' => [
                'name' => 'Owner',
                'email' => 'owner@newco.test',
                'password' => 'password123',
            ],
        ]);

        $this->assertDatabaseHas('tenants', ['name' => 'New Co', 'domain' => 'newco.test']);
        $this->assertDatabaseHas('branches', ['tenant_id' => $tenant->id, 'code' => 'HQ']);
        $this->assertDatabaseHas('users', ['email' => 'owner@newco.test', 'tenant_id' => $tenant->id]);
        $this->assertNull($tenant->fresh()->onboarding_completed_at);
    }

    public function test_super_admin_can_enter_and_exit_tenant_context(): void
    {
        $tenant = Tenant::create(['name' => 'Acme', 'is_active' => true, 'onboarding_completed_at' => now()]);
        Branch::create(['tenant_id' => $tenant->id, 'name' => 'Main', 'code' => 'M']);

        $this->actingAs($this->superAdmin);
        $context = app(TenantContext::class);

        $this->assertTrue($context->isPlatformMode());

        $context->setTenant($tenant->id);
        $this->assertSame($tenant->id, $context->currentTenantId());
        $this->assertNotNull(app(BranchContext::class)->currentBranchId());

        $this->post(route('platform.exit-tenant'))
            ->assertRedirect(route('platform.dashboard'));

        $this->assertTrue($context->isPlatformMode());
    }

    public function test_onboarding_gate_redirects_incomplete_tenant(): void
    {
        $this->withMiddleware(\App\Http\Middleware\EnsureOnboardingComplete::class);

        $tenant = Tenant::create(['name' => 'Fresh', 'is_active' => true]);
        $branch = Branch::create(['tenant_id' => $tenant->id, 'name' => 'Main', 'code' => 'M']);
        $admin = User::create([
            'tenant_id' => $tenant->id,
            'default_branch_id' => $branch->id,
            'name' => 'Admin',
            'email' => 'fresh@test.com',
            'password' => 'x',
            'is_active' => true,
        ]);
        $admin->assignRole('admin');
        $admin->branches()->attach($branch->id);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertRedirect(route('onboarding.index'));
    }

    public function test_onboarding_page_renders_under_onboarding_middleware(): void
    {
        $this->withMiddleware(\App\Http\Middleware\EnsureOnboardingComplete::class);

        $tenant = Tenant::create(['name' => 'Fresh', 'is_active' => true]);
        $branch = Branch::create(['tenant_id' => $tenant->id, 'name' => 'Main', 'code' => 'M']);
        $admin = User::create([
            'tenant_id' => $tenant->id,
            'default_branch_id' => $branch->id,
            'name' => 'Admin',
            'email' => 'fresh2@test.com',
            'password' => 'x',
            'is_active' => true,
        ]);
        $admin->assignRole('admin');
        $admin->branches()->attach($branch->id);

        $this->actingAs($admin)
            ->get(route('onboarding.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Onboarding/Index'));
    }

    public function test_onboarding_finish_completes_tenant(): void
    {
        $tenant = Tenant::create(['name' => 'Fresh', 'is_active' => true]);
        $branch = Branch::create(['tenant_id' => $tenant->id, 'name' => 'Main', 'code' => 'M']);
        $admin = User::create([
            'tenant_id' => $tenant->id,
            'default_branch_id' => $branch->id,
            'name' => 'Admin',
            'email' => 'fresh3@test.com',
            'password' => 'x',
            'is_active' => true,
        ]);
        $admin->assignRole('admin');
        $admin->branches()->attach($branch->id);
        app(BranchContext::class)->flushCache();

        $this->actingAs($admin)
            ->post(route('onboarding.finish'), [
                'company_name' => 'Fresh Kicks',
                'primary_color' => '#39C6A0',
                'secondary_color' => '#228C70',
                'locale' => 'ar',
                'timezone' => 'Africa/Khartoum',
                'currency' => 'SDG',
                'exchange_rate' => 600,
                'branch_name' => 'HQ',
                'branch_code' => 'HQ',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertNotNull($tenant->fresh()->onboarding_completed_at);
    }

    public function test_tenant_admin_pages_render(): void
    {
        $tenant = Tenant::create(['name' => 'Acme', 'is_active' => true, 'onboarding_completed_at' => now()]);
        $branch = Branch::create(['tenant_id' => $tenant->id, 'name' => 'Main', 'code' => 'M']);
        $admin = User::create([
            'tenant_id' => $tenant->id,
            'default_branch_id' => $branch->id,
            'name' => 'Admin',
            'email' => 'admin2@test.com',
            'password' => 'x',
            'is_active' => true,
        ]);
        $admin->assignRole('admin');
        $admin->branches()->attach($branch->id);
        app(BranchContext::class)->flushCache();

        $this->actingAs($admin);

        $this->get(route('branches.index'))->assertOk();
        $this->get(route('settings.index'))->assertOk();
        $this->get(route('users.index'))->assertOk();
    }
}
