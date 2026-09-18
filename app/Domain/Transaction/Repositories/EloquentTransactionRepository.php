<?php

namespace App\Domain\Transaction\Repositories;

use App\Domain\Transaction\Models\Transaction;
use App\Enums\TransactionStatus;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentTransactionRepository implements TransactionRepositoryInterface
{
    public function getPaginatedForSpg(string $spgUserId, int $perPage = 10, ?string $search = null, ?string $status = null, ?string $sortBy = null, string $sortDirection = 'asc', ?string $startDate = null, ?string $endDate = null): LengthAwarePaginator
    {
        $query = Transaction::with('outlet')
            ->where('created_by', $spgUserId);

        if ($search) {
            $query->whereHas('outlet', function ($q) use ($search) {
                $q->where('name', 'ilike', '%'.$search.'%');
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('date', '<=', $endDate . ' 23:59:59');
        }

        if ($sortBy) {
            if ($sortBy === 'outlet') {
                $query->join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
                    ->orderBy('outlets.name', $sortDirection === 'desc' ? 'desc' : 'asc')
                    ->select('transactions.*');
            } elseif ($sortBy === 'time') {
                $query->orderBy('created_at', $sortDirection === 'desc' ? 'desc' : 'asc');
            } else {
                $query->orderBy($sortBy, $sortDirection === 'desc' ? 'desc' : 'asc');
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage)
            ->withQueryString();
    }

    public function getPaginatedForSupervisor(string $supervisorId, int $perPage = 10, ?string $search = null, ?string $status = null, ?string $sortBy = null, string $sortDirection = 'asc', ?string $startDate = null, ?string $endDate = null, ?string $spgId = null): LengthAwarePaginator
    {
        $query = Transaction::with('outlet', 'createdBy')
            ->whereHas('outlet', function ($q) use ($supervisorId) {
                $q->whereHas('users', function ($q2) use ($supervisorId) {
                    $q2->where('users.id', $supervisorId);
                });
            });

        if ($search) {
            $query->whereHas('outlet', function ($q) use ($search) {
                $q->where('name', 'ilike', '%'.$search.'%');
            });
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        } elseif (! $status) {
            $query->where('status', TransactionStatus::Approval);
        }

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('date', '<=', $endDate . ' 23:59:59');
        }

        if ($spgId && $spgId !== 'all') {
            $query->where('created_by', $spgId);
        }

        if ($sortBy) {
            if ($sortBy === 'outlet') {
                $query->join('outlets', 'transactions.outlet_id', '=', 'outlets.id')
                    ->orderBy('outlets.name', $sortDirection === 'desc' ? 'desc' : 'asc')
                    ->select('transactions.*');
            } elseif ($sortBy === 'spg') {
                $query->join('users', 'transactions.created_by', '=', 'users.id')
                    ->orderBy('users.name', $sortDirection === 'desc' ? 'desc' : 'asc')
                    ->select('transactions.*');
            } elseif ($sortBy === 'time') {
                $query->orderBy('created_at', $sortDirection === 'desc' ? 'desc' : 'asc');
            } else {
                $query->orderBy($sortBy, $sortDirection === 'desc' ? 'desc' : 'asc');
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage)
            ->withQueryString();
    }

    public function getPaginatedForAdmin(int $perPage = 10, ?string $search = null, ?string $status = null, ?string $startDate = null, ?string $endDate = null, ?string $spgId = null, ?string $supervisorId = null): LengthAwarePaginator
    {
        $query = Transaction::with('outlet', 'createdBy', 'supervisorActionedBy');

        if ($search) {
            $query->whereHas('outlet', function ($q) use ($search) {
                $q->where('name', 'ilike', '%'.$search.'%');
            });
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('date', '<=', $endDate . ' 23:59:59');
        }

        if ($spgId && $spgId !== 'all') {
            $query->where('created_by', $spgId);
        }

        if ($supervisorId && $supervisorId !== 'all') {
            $query->where('supervisor_actioned_by', $supervisorId);
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
