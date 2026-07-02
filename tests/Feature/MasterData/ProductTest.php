<?php

namespace Tests\Feature\MasterData;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Branch\BranchContext;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ModuleSeeder::class);
        $this->tenant = Tenant::create(['name' => 'Acme']);
    }

    protected function actingAsAdmin(): User
    {
        $branch = Branch::create(['tenant_id' => $this->tenant->id, 'name' => 'Main', 'code' => 'M']);
        $user = User::create([
            'tenant_id' => $this->tenant->id,
            'default_branch_id' => $branch->id,
            'name' => 'Admin',
            'email' => 'a@a.com',
            'password' => 'x',
            'is_active' => true,
        ]);
        $user->assignRole('admin');
        $user->branches()->attach($branch->id);
        $this->actingAs($user);
        app(BranchContext::class)->flushCache();

        return $user;
    }

    public function test_products_are_isolated_by_tenant(): void
    {
        $otherTenant = Tenant::create(['name' => 'Other']);
        Product::factory()->for($otherTenant)->create(['name' => 'Foreign Shoe']);

        $this->actingAsAdmin();
        Product::factory()->for($this->tenant)->create(['name' => 'Our Shoe']);

        $visible = Product::query()->pluck('name');

        $this->assertContains('Our Shoe', $visible);
        $this->assertNotContains('Foreign Shoe', $visible);
        $this->assertCount(1, $visible);
    }

    public function test_admin_can_create_a_product_with_variants(): void
    {
        $this->actingAsAdmin();

        $this->post(route('products.store'), [
            'name' => 'Runner X',
            'sku' => 'RUN-X',
            'unit' => 'pair',
            'selling_price' => 25000,
            'has_variants' => 1,
            'variants' => [
                ['sku' => 'RUN-X-42', 'size' => '42', 'selling_price' => 25000],
            ],
        ])->assertRedirect(route('products.index'));

        $product = Product::where('sku', 'RUN-X')->firstOrFail();
        $this->assertSame('Runner X', $product->name);
        $this->assertTrue($product->has_variants);
        $this->assertCount(1, $product->variants);
        $this->assertSame('RUN-X-42', $product->variants->first()->sku);
    }

    public function test_sku_is_auto_generated_when_blank(): void
    {
        $this->actingAsAdmin();

        $this->post(route('products.store'), [
            'name' => 'No Sku Shoe',
            'sku' => '',
            'unit' => 'pair',
            'selling_price' => 1000,
        ])->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', ['name' => 'No Sku Shoe']);
        $this->assertNotEmpty(Product::where('name', 'No Sku Shoe')->value('sku'));
    }

    public function test_salesperson_cannot_access_product_management_route(): void
    {
        $branch = Branch::create(['tenant_id' => $this->tenant->id, 'name' => 'Main', 'code' => 'M']);
        $user = User::create([
            'tenant_id' => $this->tenant->id,
            'default_branch_id' => $branch->id,
            'name' => 'Sales',
            'email' => 's@s.com',
            'password' => 'x',
            'is_active' => true,
        ]);
        $user->assignRole('salesperson');
        $user->branches()->attach($branch->id);
        $this->actingAs($user);

        // salesperson has products.view, so index is reachable...
        $this->get('/products')->assertOk();

        // ...but cannot create.
        $this->post(route('products.store'), [
            'name' => 'X',
            'unit' => 'pair',
            'selling_price' => 1,
        ])->assertForbidden();
    }
}
