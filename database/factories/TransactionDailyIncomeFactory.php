<?php

namespace Database\Factories;

use App\Domain\Outlet\Models\Chair;
use App\Domain\Transaction\Models\Transaction;
use App\Domain\Transaction\Models\TransactionDailyIncome;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionDailyIncome>
 */
class TransactionDailyIncomeFactory extends Factory
{
    protected $model = TransactionDailyIncome::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_id' => Transaction::factory(),
            'chair_id' => Chair::factory(),
            'amount' => fake()->randomFloat(2, 10000, 500000),
        ];
    }
}
