<?php

namespace App\Domain\Transaction\Repositories;

use Illuminate\Support\Collection;

interface TransactionSystemIncomeRepositoryInterface
{
    /**
     * Upsert system incomes for a transaction.
     *
     * @param  array<int, array{chair_id: string, amount: float}>  $items
     */
    public function upsertForTransaction(string $transactionId, array $items): void;

    /**
     * Find all system incomes for a transaction.
     */
    public function findByTransactionId(string $transactionId): Collection;
}
