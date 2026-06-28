<?php

namespace Tests\Feature\Auth;

use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(bool $active = true): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $tenant = Tenant::create(['name' => 'Acme']);
        $branch = Branch::create(['tenant_id' => $tenant->id, 'name' => 'Main', 'code' => 'M1']);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'default_branch_id' => $branch->id,
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'password' => Hash::make('secret-pass'),
            'is_active' => $active,
        ]);

        $user->assignRole('admin');
        $user->branches()->attach($branch->id, ['is_primary' => true]);

        return $user;
    }

    public function test_login_screen_is_reachable_for_guests(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_user_can_authenticate_with_valid_credentials(): void
    {
        $this->makeUser();

        Livewire::test('auth.login')
            ->set('email', 'jane@example.com')
            ->set('password', 'secret-pass')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_user_cannot_authenticate_with_invalid_password(): void
    {
        $this->makeUser();

        Livewire::test('auth.login')
            ->set('email', 'jane@example.com')
            ->set('password', 'wrong')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_users_cannot_authenticate(): void
    {
        $this->makeUser(active: false);

        Livewire::test('auth.login')
            ->set('email', 'jane@example.com')
            ->set('password', 'secret-pass')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }
}
