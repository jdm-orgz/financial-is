<?php

namespace App\Domain\Outlet\Repositories;

use App\Domain\Outlet\Models\LinkedOutletUser;
use App\Domain\UserAccess\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentLinkedOutletUserRepository implements LinkedOutletUserRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function getPaginated(int $perPage = 10, ?string $search = null, ?string $sortBy = null, string $sortDirection = 'asc', array $roleIds = []): LengthAwarePaginator
    {
        $query = User::whereHas('outlets')->with(['role', 'outlets']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('outlets', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($roleIds)) {
            $query->whereHas('role', function ($q) use ($roleIds) {
                $q->whereIn('name', $roleIds);
            });
        }

        if ($sortBy) {
            if ($sortBy === 'user.name') {
                $query->orderBy('name', $sortDirection);
            } else {
                // If it's another column, make sure it exists on users table, or default to name
                $query->orderBy('name', $sortDirection);
            }
        } else {
            $query->latest('created_at');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * {@inheritDoc}
     */
    public function findById(string $id): ?LinkedOutletUser
    {
        return LinkedOutletUser::query()->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): LinkedOutletUser
    {
        return LinkedOutletUser::query()->create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function update(string $id, array $data): bool
    {
        $linkedOutletUser = $this->findById($id);

        if (! $linkedOutletUser) {
            return false;
        }

        return $linkedOutletUser->update($data);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $id): bool
    {
        $linkedOutletUser = $this->findById($id);

        if (! $linkedOutletUser) {
            return false;
        }

        return $linkedOutletUser->delete();
    }
}
