<?php

namespace Tests\Feature\Shop;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Branch\BranchContext;
use App\Services\Shop\ShopSettingsService;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicShopTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Product $visibleProduct;

    protected Product $hiddenProduct;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ModuleSeeder::class);

        $this->tenant = Tenant::create(['name' => 'Mazin Shoes', 'is_active' => true]);
        Branch::create(['tenant_id' => $this->tenant->id, 'name' => 'Main', 'code' => 'M']);

        $category = Category::create(['tenant_id' => $this->tenant->id, 'name' => 'Sneakers']);

        $this->visibleProduct = Product::factory()->for($this->tenant)->create([
            'category_id' => $category->id,
            'name' => 'Air Runner',
            'show_in_shop' => true,
            'featured_in_shop' => true,
            'shop_description' => 'Lightweight daily trainer',
            'is_active' => true,
        ]);

        ProductVariant::create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->visibleProduct->id,
            'sku' => 'AR-42-BLK',
            'options' => ['size' => '42', 'color' => 'Black'],
            'selling_price' => 2500,
            'is_active' => true,
        ]);

        $this->hiddenProduct = Product::factory()->for($this->tenant)->create([
            'name' => 'Hidden',
            'show_in_shop' => false,
            'is_active' => true,
        ]);

        app(ShopSettingsService::class)->save($this->tenant, array_merge(
            app(ShopSettingsService::class)->defaults(),
            ['enabled' => true, 'show_prices' => true],
        ));
    }

    public function test_public_shop_accessible_without_auth(): void
    {
        $this->get(route('shop.index', $this->tenant->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Shop/Index')
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Air Runner'));
    }

    public function test_disabled_shop_returns_404(): void
    {
        app(ShopSettingsService::class)->save($this->tenant, ['enabled' => false]);

        $this->get(route('shop.index', $this->tenant->slug))->assertNotFound();
    }

    public function test_admin_can_preview_disabled_shop(): void
    {
        app(ShopSettingsService::class)->save($this->tenant, ['enabled' => false]);

        $branch = Branch::query()->where('tenant_id', $this->tenant->id)->firstOrFail();
        $admin = User::create([
            'tenant_id' => $this->tenant->id,
            'default_branch_id' => $branch->id,
            'name' => 'Admin',
            'email' => 'admin@a.com',
            'password' => 'x',
            'is_active' => true,
        ]);
        $admin->assignRole('admin');
        $admin->branches()->attach($branch->id);
        app(BranchContext::class)->flushCache();

        $this->actingAs($admin)
            ->get(route('shop.index', $this->tenant->slug))
            ->assertOk();
    }

    public function test_prices_hidden_when_setting_off(): void
    {
        app(ShopSettingsService::class)->save($this->tenant, ['enabled' => true, 'show_prices' => false]);

        $this->get(route('shop.index', $this->tenant->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('shop.show_prices', false));
    }

    public function test_product_detail_shows_variant_specs(): void
    {
        $this->get(route('shop.show', ['tenant' => $this->tenant->slug, 'product' => $this->visibleProduct->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Shop/Show')
                ->where('product.variants.0.sku', 'AR-42-BLK')
                ->where('product.variants.0.size', '42'));
    }

    public function test_hidden_product_not_listed_or_accessible(): void
    {
        $this->get(route('shop.index', $this->tenant->slug))
            ->assertInertia(fn ($page) => $page->has('products.data', 1));

        $this->get(route('shop.show', ['tenant' => $this->tenant->slug, 'product' => $this->hiddenProduct->id]))
            ->assertNotFound();
    }
}
