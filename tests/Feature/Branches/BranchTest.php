<?php

namespace Tests\Feature\Branches;

use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Branch\BranchContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BranchTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->tenant = Tenant::create(['name' => 'Acme', 'is_active' => true, 'onboarding_completed_at' => now()]);
        $branch = Branch::create(['tenant_id' => $this->tenant->id, 'name' => 'Main', 'code' => 'M', 'is_active' => true]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'default_branch_id' => $branch->id,
            'name' => 'Admin',
            'email' => 'admin@acme.test',
            'password' => 'secret',
            'is_active' => true,
        ]);
        $this->admin->assignRole('admin');
        $this->admin->branches()->attach($branch->id, ['is_primary' => true]);
        app(BranchContext::class)->flushCache();
    }

    public function test_index_renders_inertia_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('branches.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Branches/Index')->has('branches.data'));
    }

    public function test_admin_can_create_a_branch_with_default_stock_location(): void
    {
        $this->actingAs($this->admin)
            ->post(route('branches.store'), [
                'name' => 'Downtown',
                'code' => 'DT',
                'primary_color' => '#39C6A0',
                'secondary_color' => '#228C70',
                'is_active' => true,
            ])
            ->assertRedirect(route('branches.index'));

        $this->assertDatabaseHas('branches', ['code' => 'DT', 'tenant_id' => $this->tenant->id]);
        $branch = Branch::where('code', 'DT')->first();
        $this->assertDatabaseHas('stock_locations', ['branch_id' => $branch->id, 'code' => 'MAIN']);
    }

    public function test_admin_can_update_a_branch(): void
    {
        $branch = Branch::create(['tenant_id' => $this->tenant->id, 'name' => 'Old', 'code' => 'OLD', 'is_active' => true]);

        $this->actingAs($this->admin)
            ->put(route('branches.update', $branch), [
                'name' => 'New name',
                'code' => 'OLD',
                'primary_color' => '#39C6A0',
                'secondary_color' => '#228C70',
                'is_active' => true,
            ])
            ->assertRedirect(route('branches.index'));

        $this->assertDatabaseHas('branches', ['id' => $branch->id, 'name' => 'New name']);
    }

    public function test_cannot_deactivate_the_last_active_branch(): void
    {
        $branch = Branch::where('code', 'M')->first();

        $this->actingAs($this->admin)
            ->put(route('branches.update', $branch), [
                'name' => 'Main',
                'code' => 'M',
                'primary_color' => '#39C6A0',
                'secondary_color' => '#228C70',
                'is_active' => false,
            ])
            ->assertRedirect(route('branches.index'));

        $this->assertDatabaseHas('branches', ['id' => $branch->id, 'is_active' => true]);
    }

    public function test_validation_errors_are_returned(): void
    {
        $this->actingAs($this->admin)
            ->post(route('branches.store'), ['name' => '', 'code' => ''])
            ->assertSessionHasErrors(['name', 'code']);
    }
}
