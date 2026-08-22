<?php

namespace App\Domain\Transaction\Repositories;

use App\Domain\Transaction\Models\TransactionReplacementRealization;
use Illuminate\Support\Collection;

class EloquentTransactionReplacementRealizationRepository implements TransactionReplacementRealizationRepositoryInterface
{
    public function create(array $data): TransactionReplacementRealization
    {
        return TransactionReplacementRealization::create($data);
    }

    public function update(string $id, array $data): bool
    {
        $realization = $this->findById($id);

        if (! $realization) {
            return false;
        }

        return $realization->update($data);
    }

    public function delete(string $id): bool
    {
        $realization = $this->findById($id);

        if (! $realization) {
            return false;
        }

        return $realization->delete();
    }

    public function findById(string $id): ?TransactionReplacementRealization
    {
        return TransactionReplacementRealization::find($id);
    }

    public function findByTransactionId(string $transactionId): Collection
    {
        return TransactionReplacementRealization::with('problemChair', 'replacementChair')
            ->where('transaction_id', $transactionId)
            ->get();
    }
}
