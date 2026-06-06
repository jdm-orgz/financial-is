<?php

namespace App\Domain\UserAccess\Repositories;

use App\Domain\UserAccess\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function getPaginated(int $perPage = 10, ?string $search = null, ?string $sortBy = null, string $sortDirection = 'asc'): LengthAwarePaginator
    {
        $query = User::with('role');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($sortBy) {
            // Support sorting by role name?
            // Eloquent doesn't support easy relationship sorting without joins.
            // We'll just sort on the user fields.
            $query->orderBy($sortBy, $sortDirection === 'desc' ? 'desc' : 'asc');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function findById(string $id): ?User
    {
        return User::find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(string $id, array $data): bool
    {
        $user = $this->findById($id);

        if (! $user) {
            return false;
        }

        return $user->update($data);
    }

    public function delete(string $id): bool
    {
        $user = $this->findById($id);

        if (! $user) {
            return false;
        }

        return $user->delete();
    }
}
