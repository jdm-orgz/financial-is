<?php

namespace App\Domain\Transaction\Repositories;

use App\Domain\Transaction\Models\TransactionReplacementRealization;
use Illuminate\Support\Collection;

interface TransactionReplacementRealizationRepositoryInterface
{
    /**
     * Create a new replacement realization.
     */
    public function create(array $data): TransactionReplacementRealization;

    /**
     * Update an existing replacement realization.
     */
    public function update(string $id, array $data): bool;

    /**
     * Delete a replacement realization.
     */
    public function delete(string $id): bool;

    /**
     * Find a replacement realization by ID.
     */
    public function findById(string $id): ?TransactionReplacementRealization;

    /**
     * Find all replacement realizations for a transaction.
     */
    public function findByTransactionId(string $transactionId): Collection;
}
