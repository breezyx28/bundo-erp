<?php

namespace Tests\Feature\Security;

use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Branch\BranchContext;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaShellTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ModuleSeeder::class);

        $tenant = Tenant::create(['name' => 'Acme']);
        $branch = Branch::create(['tenant_id' => $tenant->id, 'name' => 'Main', 'code' => 'M']);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'default_branch_id' => $branch->id,
            'name' => 'Admin',
            'email' => 'admin@acme.test',
            'password' => 'secret',
            'is_active' => true,
        ]);
        $user->assignRole('admin');
        $user->branches()->attach($branch->id);
        $this->actingAs($user);
        app(BranchContext::class)->flushCache();
    }

    public function test_layout_exposes_pwa_shell_and_accessibility_landmarks(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('rel="manifest"', false);
        $response->assertSee('/sw.js', false);
        $response->assertSee('id="main-content"', false);
    }
}
