<?php

namespace Database\Factories;

use App\Models\ExpenseCategory;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ExpenseCategory> */
class ExpenseCategoryFactory extends Factory
{
    protected $model = ExpenseCategory::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => ucfirst($this->faker->unique()->word()),
            'is_operational' => true,
            'is_active' => true,
        ];
    }
}
