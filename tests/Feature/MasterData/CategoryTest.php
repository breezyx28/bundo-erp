<?php

namespace Tests\Feature\MasterData;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Branch\BranchContext;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
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
            'password' => 'x',
            'is_active' => true,
        ]);
        $user->assignRole('admin');
        $user->branches()->attach($this->branch->id);
        $this->actingAs($user);
        app(BranchContext::class)->flushCache();

        return $user;
    }

    public function test_can_create_root_category(): void
    {
        $this->actingAsAdmin();

        $this->post(route('categories.store'), [
            'name' => 'Footwear',
            'parent_id' => null,
            'is_active' => true,
        ])->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Footwear',
            'parent_id' => null,
        ]);
    }

    public function test_can_create_subcategory_under_root(): void
    {
        $this->actingAsAdmin();
        $root = Category::create(['tenant_id' => $this->tenant->id, 'name' => 'Footwear']);

        $this->post(route('categories.store'), [
            'name' => 'Sandals',
            'parent_id' => $root->id,
            'is_active' => true,
        ])->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Sandals',
            'parent_id' => $root->id,
        ]);
    }

    public function test_can_change_parent_and_clear_parent(): void
    {
        $this->actingAsAdmin();
        $rootA = Category::create(['tenant_id' => $this->tenant->id, 'name' => 'Footwear']);
        $rootB = Category::create(['tenant_id' => $this->tenant->id, 'name' => 'Accessories']);
        $child = Category::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Sandals',
            'parent_id' => $rootA->id,
        ]);

        $this->put(route('categories.update', $child), [
            'name' => 'Sandals',
            'parent_id' => $rootB->id,
            'is_active' => true,
        ])->assertRedirect(route('categories.index'));

        $this->assertSame($rootB->id, $child->fresh()->parent_id);

        $this->put(route('categories.update', $child), [
            'name' => 'Sandals',
            'parent_id' => null,
            'is_active' => true,
        ])->assertRedirect(route('categories.index'));

        $this->assertNull($child->fresh()->parent_id);
    }

    public function test_rejects_self_parent(): void
    {
        $this->actingAsAdmin();
        $category = Category::create(['tenant_id' => $this->tenant->id, 'name' => 'Footwear']);

        $this->put(route('categories.update', $category), [
            'name' => 'Footwear',
            'parent_id' => $category->id,
            'is_active' => true,
        ])->assertSessionHasErrors('parent_id');
    }

    public function test_rejects_child_category_as_parent(): void
    {
        $this->actingAsAdmin();
        $root = Category::create(['tenant_id' => $this->tenant->id, 'name' => 'Footwear']);
        $child = Category::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Sandals',
            'parent_id' => $root->id,
        ]);

        $this->post(route('categories.store'), [
            'name' => 'Nested',
            'parent_id' => $child->id,
            'is_active' => true,
        ])->assertSessionHasErrors('parent_id');
    }

    public function test_rejects_parent_for_category_with_children(): void
    {
        $this->actingAsAdmin();
        $rootA = Category::create(['tenant_id' => $this->tenant->id, 'name' => 'Footwear']);
        $rootB = Category::create(['tenant_id' => $this->tenant->id, 'name' => 'Accessories']);
        $parent = Category::create(['tenant_id' => $this->tenant->id, 'name' => 'Shoes']);
        Category::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Sneakers',
            'parent_id' => $parent->id,
        ]);

        $this->put(route('categories.update', $parent), [
            'name' => 'Shoes',
            'parent_id' => $rootB->id,
            'is_active' => true,
        ])->assertSessionHasErrors('parent_id');
    }
}
