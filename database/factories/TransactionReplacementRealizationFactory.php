<?php

namespace Database\Factories;

use App\Domain\Outlet\Models\Chair;
use App\Domain\Transaction\Models\Transaction;
use App\Domain\Transaction\Models\TransactionReplacementRealization;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionReplacementRealization>
 */
class TransactionReplacementRealizationFactory extends Factory
{
    protected $model = TransactionReplacementRealization::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_id' => Transaction::factory(),
            'problem_chair_id' => Chair::factory(),
            'replacement_chair_id' => Chair::factory(),
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'amount' => fake()->numberBetween(1, 100) * 5000,
            'proof_video_path' => 'proofs/videos/test_video.mp4',
        ];
    }

    /**
     * Set payment method to QRIS with proof image.
     */
    public function qris(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => PaymentMethod::Qris,
            'proof_image_path' => 'proofs/images/test_qris.jpg',
        ]);
    }

    /**
     * Set payment method to cash.
     */
    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => PaymentMethod::Cash,
            'proof_image_path' => null,
        ]);
    }
}
