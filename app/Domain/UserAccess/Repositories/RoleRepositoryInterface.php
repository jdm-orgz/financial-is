<?php

namespace App\Domain\UserAccess\Repositories;

use App\Domain\UserAccess\Models\Role;
use Illuminate\Pagination\LengthAwarePaginator;

interface RoleRepositoryInterface
{
    /**
     * Get paginated roles.
     */
    public function getPaginated(int $perPage = 10, ?string $search = null, ?string $sortBy = null, string $sortDirection = 'asc'): LengthAwarePaginator;

    /**
     * Get a role by ID.
     */
    public function findById(int $id): ?Role;

    /**
     * Create a new role.
     */
    public function create(array $data): Role;

    /**
     * Update an existing role.
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a role.
     */
    public function delete(int $id): bool;
}
