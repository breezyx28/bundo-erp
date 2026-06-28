<?php

namespace Database\Factories;

use App\Models\Supplier;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Supplier> */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->company(),
            'contact_person' => $this->faker->name(),
            'phone' => $this->faker->numerify('+24990#######'),
            'opening_balance' => 0,
            'is_active' => true,
        ];
    }
}
