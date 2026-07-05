<?php

namespace Tests\Feature\Layout;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Branch\BranchContext;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TabletModeTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ModuleSeeder::class);
        $this->tenant = Tenant::create(['name' => 'Acme']);
        $this->branch = Branch::create(['tenant_id' => $this->tenant->id, 'name' => 'A', 'code' => 'A']);
    }

    protected function actingAsAdmin(): User
    {
        $user = User::create([
            'tenant_id' => $this->tenant->id,
            'default_branch_id' => $this->branch->id,
            'name' => 'Admin',
            'email' => 'a@a.com',
            'password' => 'secret-password',
            'is_active' => true,
        ]);
        $user->assignRole('admin');
        $user->branches()->attach($this->branch->id);
        $this->actingAs($user);
        app(BranchContext::class)->flushCache();

        return $user;
    }

    public function test_layout_preference_saves_and_is_shared(): void
    {
        $user = $this->actingAsAdmin();

        $this->post(route('preferences.layout'), ['layout_mode' => 'tablet'])
            ->assertRedirect();

        $this->assertSame('tablet', data_get($user->fresh()->settings, 'layout_mode'));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('layout.mode', 'tablet'));
    }

    public function test_layout_preference_rejects_invalid_mode(): void
    {
        $this->actingAsAdmin();

        $this->post(route('preferences.layout'), ['layout_mode' => 'phone'])
            ->assertSessionHasErrors('layout_mode');
    }

    public function test_links_page_renders_with_stats(): void
    {
        $this->actingAsAdmin();

        $this->get(route('links.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Links/Index')
                ->has('stats', 4)
            );
    }

    public function test_login_redirects_to_links_in_tablet_mode(): void
    {
        $user = $this->actingAsAdmin();
        $user->forceFill(['settings' => ['layout_mode' => 'tablet']])->save();

        auth()->logout();

        $this->post(route('login.store'), [
            'email' => 'a@a.com',
            'password' => 'secret-password',
        ])->assertRedirect(route('links.index'));
    }

    public function test_login_redirects_to_dashboard_in_regular_mode(): void
    {
        $this->actingAsAdmin();

        auth()->logout();

        $this->post(route('login.store'), [
            'email' => 'a@a.com',
            'password' => 'secret-password',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_suggestions_endpoint_returns_whitelisted_values(): void
    {
        $this->actingAsAdmin();

        Customer::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Ahmed',
            'address' => 'Khartoum, Street 15',
        ]);

        $this->getJson(route('suggestions', ['field' => 'customer_address', 'q' => 'Khart']))
            ->assertOk()
            ->assertJson(['Khartoum, Street 15']);

        $this->getJson(route('suggestions', ['field' => 'users_password']))
            ->assertOk()
            ->assertExactJson([]);
    }
}
