<?php

namespace App\Domain\Transaction\Repositories;

use App\Domain\Transaction\Models\TransactionDailyIncome;
use Illuminate\Support\Collection;

class EloquentTransactionDailyIncomeRepository implements TransactionDailyIncomeRepositoryInterface
{
    public function upsertForTransaction(string $transactionId, array $items): void
    {
        foreach ($items as $item) {
            TransactionDailyIncome::updateOrCreate(
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

    public function deleteByTransactionId(string $transactionId): void
    {
        TransactionDailyIncome::where('transaction_id', $transactionId)->delete();
    }

    public function findByTransactionId(string $transactionId): Collection
    {
        return TransactionDailyIncome::with('chair')
            ->where('transaction_id', $transactionId)
            ->get();
    }
}
