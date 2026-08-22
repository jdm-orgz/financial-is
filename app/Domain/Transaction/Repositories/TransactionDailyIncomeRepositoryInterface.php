<?php

namespace App\Domain\Transaction\Repositories;

use Illuminate\Support\Collection;

interface TransactionDailyIncomeRepositoryInterface
{
    /**
     * Upsert daily incomes for a transaction.
     *
     * @param  array<int, array{chair_id: string, amount: float}>  $items
     */
    public function upsertForTransaction(string $transactionId, array $items): void;

    /**
     * Delete all daily incomes for a transaction.
     */
    public function deleteByTransactionId(string $transactionId): void;

    /**
     * Find all daily incomes for a transaction.
     */
    public function findByTransactionId(string $transactionId): Collection;
}
