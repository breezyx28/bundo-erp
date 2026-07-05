<?php

namespace Tests\Feature\Preferences;

use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisplayPreferenceTest extends TestCase
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

        return $user;
    }

    public function test_display_preferences_page_requires_auth(): void
    {
        $this->get(route('preferences.display'))
            ->assertRedirect(route('login'));
    }

    public function test_display_preferences_page_renders(): void
    {
        $this->actingAsAdmin();

        $this->get(route('preferences.display'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Preferences/Display')
                ->where('scale', 'md')
            );
    }

    public function test_display_preferences_save_and_share(): void
    {
        $user = $this->actingAsAdmin();

        $this->post(route('preferences.display.save'), [
            'scale' => 'lg',
            'textBody' => '#111827',
            'textMuted' => '#6b7280',
            'highContrast' => false,
        ])->assertRedirect();

        $display = data_get($user->fresh()->settings, 'display');
        $this->assertSame('lg', data_get($display, 'scale'));
        $this->assertSame('#111827', data_get($display, 'text_body'));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('displayPrefs.scale', 'lg'));
    }

    public function test_display_preferences_rejects_invalid_scale(): void
    {
        $this->actingAsAdmin();

        $this->post(route('preferences.display.save'), [
            'scale' => 'huge',
            'highContrast' => false,
        ])->assertSessionHasErrors('scale');
    }

    public function test_display_preferences_rejects_invalid_hex(): void
    {
        $this->actingAsAdmin();

        $this->post(route('preferences.display.save'), [
            'scale' => 'md',
            'textBody' => 'not-a-color',
            'highContrast' => false,
        ])->assertSessionHasErrors('textBody');
    }
}
