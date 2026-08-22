<?php

namespace Database\Factories;

use App\Domain\Outlet\Models\Outlet;
use App\Domain\Transaction\Models\Transaction;
use App\Domain\UserAccess\Models\User;
use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'outlet_id' => Outlet::factory(),
            'date' => fake()->date(),
            'status' => TransactionStatus::Draft,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Set the transaction status to approval.
     */
    public function approval(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TransactionStatus::Approval,
        ]);
    }

    /**
     * Set the transaction status to correction.
     */
    public function correction(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TransactionStatus::Correction,
        ]);
    }

    /**
     * Set the transaction status to comparing.
     */
    public function comparing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TransactionStatus::Comparing,
            'supervisor_actioned_by' => User::factory(),
            'supervisor_actioned_at' => now(),
        ]);
    }

    /**
     * Set the transaction status to compared.
     */
    public function compared(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TransactionStatus::Compared,
            'supervisor_actioned_by' => User::factory(),
            'supervisor_actioned_at' => now(),
            'admin_actioned_by' => User::factory(),
            'admin_actioned_at' => now(),
        ]);
    }

    /**
     * Set the transaction status to done.
     */
    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TransactionStatus::Done,
            'supervisor_actioned_by' => User::factory(),
            'supervisor_actioned_at' => now(),
            'admin_actioned_by' => User::factory(),
            'admin_actioned_at' => now(),
        ]);
    }
}
