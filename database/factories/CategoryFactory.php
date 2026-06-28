<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Category> */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        return [
            'tenant_id' => Tenant::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'is_active' => true,
        ];
    }
}
