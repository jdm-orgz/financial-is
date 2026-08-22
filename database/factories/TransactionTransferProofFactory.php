<?php

namespace Database\Factories;

use App\Domain\Transaction\Models\Transaction;
use App\Domain\Transaction\Models\TransactionTransferProof;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionTransferProof>
 */
class TransactionTransferProofFactory extends Factory
{
    protected $model = TransactionTransferProof::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_id' => Transaction::factory(),
            'proof_image_path' => 'proofs/transfers/test_transfer.jpg',
        ];
    }
}
