<?php

namespace App\Domain\Transaction\Repositories;

use App\Domain\Transaction\Models\TransactionSystemIncome;
use Illuminate\Support\Collection;

class EloquentTransactionSystemIncomeRepository implements TransactionSystemIncomeRepositoryInterface
{
    public function upsertForTransaction(string $transactionId, array $items): void
    {
        foreach ($items as $item) {
            TransactionSystemIncome::updateOrCreate(
                [
                    'transaction_id' => $transactionId,
                    'chair_id' => $item['chair_id'],
                ],
                [
                    'amount' => $item['amount'],
                ]
            );
        }
    }

    public function findByTransactionId(string $transactionId): Collection
    {
        return TransactionSystemIncome::with('chair')
            ->where('transaction_id', $transactionId)
            ->get();
    }
}
