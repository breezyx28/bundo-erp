<?php

namespace Tests\Feature\Shop;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Branch\BranchContext;
use App\Services\Shop\ShopSettingsService;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ModuleSeeder::class);

        $this->tenant = Tenant::create(['name' => 'Acme', 'is_active' => true]);
        $branch = Branch::create(['tenant_id' => $this->tenant->id, 'name' => 'Main', 'code' => 'M']);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'default_branch_id' => $branch->id,
            'name' => 'Admin',
            'email' => 'shop@a.com',
            'password' => 'x',
            'is_active' => true,
        ]);
        $this->admin->assignRole('admin');
        $this->admin->branches()->attach($branch->id);
        app(BranchContext::class)->flushCache();
    }

    public function test_admin_can_save_shop_contacts_and_enable_shop(): void
    {
        $this->actingAs($this->admin)
            ->post(route('shop.settings.save'), [
                'enabled' => true,
                'show_prices' => true,
                'hero_title' => 'Welcome',
                'hero_subtitle' => 'Best shoes in town',
                'share_message' => 'Check this out',
                'contact' => [
                    'phone' => '+249123456789',
                    'whatsapp' => '+249123456789',
                    'instagram' => '@acme',
                ],
                'banners' => [],
            ])
            ->assertRedirect(route('shop.settings'));

        $settings = app(ShopSettingsService::class)->forTenant($this->tenant);
        $this->assertTrue($settings['enabled']);
        $this->assertSame('Welcome', $settings['hero_title']);
        $this->assertSame('+249123456789', $settings['contact']['whatsapp']);
    }

    public function test_hero_title_required_when_shop_enabled(): void
    {
        $this->actingAs($this->admin)
            ->post(route('shop.settings.save'), [
                'enabled' => true,
                'show_prices' => true,
                'hero_title' => '',
                'banners' => [],
            ])
            ->assertSessionHasErrors('hero_title');
    }

    public function test_admin_can_toggle_product_visibility(): void
    {
        $product = Product::factory()->for($this->tenant)->create(['show_in_shop' => false]);

        $this->actingAs($this->admin)
            ->post(route('products.shop.toggle', $product), ['show_in_shop' => true])
            ->assertRedirect();

        $this->assertTrue($product->fresh()->show_in_shop);
    }
}
