<?php

namespace Database\Factories;

use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Expense> */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'amount' => $this->faker->numberBetween(1000, 50000),
            'description' => $this->faker->sentence(),
            'expense_date' => now()->toDateString(),
            'payment_method' => Expense::METHOD_CASH,
        ];
    }
}
