<?php

namespace App\Domain\Transaction\Repositories;

use App\Domain\Transaction\Models\TransactionTransferProof;
use Illuminate\Support\Collection;

class EloquentTransactionTransferProofRepository implements TransactionTransferProofRepositoryInterface
{
    public function create(array $data): TransactionTransferProof
    {
        return TransactionTransferProof::create($data);
    }

    public function delete(string $id): bool
    {
        $proof = $this->findById($id);

        if (! $proof) {
            return false;
        }

        return $proof->delete();
    }

    public function findById(string $id): ?TransactionTransferProof
    {
        return TransactionTransferProof::find($id);
    }

    public function findByTransactionId(string $transactionId): Collection
    {
        return TransactionTransferProof::where('transaction_id', $transactionId)->get();
    }
}
