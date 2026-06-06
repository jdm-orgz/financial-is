<?php

namespace App\Domain\UserAccess\Repositories;

use App\Domain\UserAccess\Models\Role;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentRoleRepository implements RoleRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function getPaginated(int $perPage = 10, ?string $search = null, ?string $sortBy = null, string $sortDirection = 'asc'): LengthAwarePaginator
    {
        $query = Role::query();

        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        }

        if ($sortBy) {
            $query->orderBy($sortBy, $sortDirection === 'desc' ? 'desc' : 'asc');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * {@inheritDoc}
     */
    public function findById(int $id): ?Role
    {
        return Role::query()->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): Role
    {
        return Role::query()->create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function update(int $id, array $data): bool
    {
        $role = $this->findById($id);

        if (! $role) {
            return false;
        }

        return $role->update($data);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(int $id): bool
    {
        $role = $this->findById($id);

        if (! $role) {
            return false;
        }

        return $role->delete();
    }
}
