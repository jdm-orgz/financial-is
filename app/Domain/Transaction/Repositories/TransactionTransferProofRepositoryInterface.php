<?php

namespace App\Domain\Transaction\Repositories;

use App\Domain\Transaction\Models\TransactionTransferProof;
use Illuminate\Support\Collection;

interface TransactionTransferProofRepositoryInterface
{
    /**
     * Create a new transfer proof.
     */
    public function create(array $data): TransactionTransferProof;

    /**
     * Delete a transfer proof.
     */
    public function delete(string $id): bool;

    /**
     * Find a transfer proof by ID.
     */
    public function findById(string $id): ?TransactionTransferProof;

    /**
     * Find all transfer proofs for a transaction.
     */
    public function findByTransactionId(string $transactionId): Collection;
}
