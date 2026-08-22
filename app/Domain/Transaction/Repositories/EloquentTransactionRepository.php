<?php

namespace App\Domain\Transaction\Repositories;

use App\Domain\Transaction\Models\Transaction;
use App\Enums\TransactionStatus;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentTransactionRepository implements TransactionRepositoryInterface
{
    public function getPaginatedForSpg(string $spgUserId, int $perPage = 10, ?string $search = null, ?string $status = null): LengthAwarePaginator
    {
        $query = Transaction::with('outlet')
            ->where('created_by', $spgUserId);

        if ($search) {
            $query->whereHas('outlet', function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%');
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getPaginatedForSupervisor(string $supervisorId, int $perPage = 10, ?string $status = null): LengthAwarePaginator
    {
        $query = Transaction::with('outlet', 'createdBy')
            ->whereHas('outlet', function ($q) use ($supervisorId) {
                $q->whereHas('users', function ($q2) use ($supervisorId) {
                    $q2->where('users.id', $supervisorId);
                });
            });

        if ($status) {
            $query->where('status', $status);
        } else {
            $query->where('status', TransactionStatus::Approval);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getPaginatedForAdmin(int $perPage = 10, ?string $status = null): LengthAwarePaginator
    {
        $query = Transaction::with('outlet', 'createdBy');

        if ($status) {
            $query->where('status', $status);
        } else {
            $query->where('status', TransactionStatus::Comparing);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getPaginatedAll(int $perPage = 10, ?string $search = null, ?string $status = null): LengthAwarePaginator
    {
        $query = Transaction::with('outlet', 'createdBy');

        if ($search) {
            $query->whereHas('outlet', function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%');
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findById(string $id): ?Transaction
    {
        return Transaction::with([
            'outlet',
            'dailyIncomes.chair',
            'replacementRealizations.problemChair',
            'replacementRealizations.replacementChair',
            'transferProofs',
            'systemIncomes.chair',
            'createdBy',
            'supervisorActionedBy',
            'adminActionedBy',
        ])->find($id);
    }

    public function create(array $data): Transaction
    {
        return Transaction::create($data);
    }

    public function updateStatus(string $id, TransactionStatus $status, array $extra = []): bool
    {
        $transaction = Transaction::find($id);

        if (! $transaction) {
            return false;
        }

        return $transaction->update(array_merge(['status' => $status], $extra));
    }

    public function existsForOutletAndDate(string $outletId, string $date, ?string $excludeId = null): bool
    {
        $query = Transaction::where('outlet_id', $outletId)->whereDate('date', $date);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function delete(string $id): bool
    {
        $transaction = Transaction::find($id);

        if (! $transaction) {
            return false;
        }

        return $transaction->delete();
    }
}
