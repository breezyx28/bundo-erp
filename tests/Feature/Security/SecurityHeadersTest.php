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

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ModuleSeeder::class);
    }

    public function test_security_headers_present_on_guest_pages(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertNotEmpty($response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("object-src 'none'", (string) $response->headers->get('Content-Security-Policy'));
    }

    public function test_security_headers_present_on_authenticated_pages(): void
    {
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

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }
}
