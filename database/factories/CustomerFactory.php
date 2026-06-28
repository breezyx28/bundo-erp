<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Customer> */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->name(),
            'phone' => $this->faker->numerify('+24991#######'),
            'type' => $this->faker->randomElement(['retail', 'wholesale']),
            'credit_limit' => $this->faker->randomElement([0, 50000, 100000]),
            'opening_balance' => 0,
            'is_active' => true,
        ];
    }

    public function wholesale(): static
    {
        return $this->state(fn () => ['type' => 'wholesale']);
    }
}
