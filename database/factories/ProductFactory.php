<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $cost = $this->faker->numberBetween(2000, 30000);

        return [
            'tenant_id' => Tenant::factory(),
            'name' => ucfirst($this->faker->words(2, true)),
            'sku' => 'SKU-'.Str::upper(Str::random(8)),
            'unit' => 'pair',
            'cost_price' => $cost,
            'selling_price' => $cost * 1.4,
            'reorder_level' => $this->faker->numberBetween(0, 10),
            'is_active' => true,
        ];
    }
}
