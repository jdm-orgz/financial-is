<?php

namespace App\Domain\Outlet\Repositories;

use App\Domain\Outlet\Models\LinkedOutletUser;
use Illuminate\Pagination\LengthAwarePaginator;

interface LinkedOutletUserRepositoryInterface
{
    /**
     * Get paginated linked outlet users.
     */
    public function getPaginated(int $perPage = 10, ?string $search = null, ?string $sortBy = null, string $sortDirection = 'asc', array $roleIds = []): LengthAwarePaginator;

    /**
     * Get a linked outlet user by ID.
     */
    public function findById(string $id): ?LinkedOutletUser;

    /**
     * Create a new linked outlet user.
     */
    public function create(array $data): LinkedOutletUser;

    /**
     * Update an existing linked outlet user.
     */
    public function update(string $id, array $data): bool;

    /**
     * Delete a linked outlet user.
     */
    public function delete(string $id): bool;
}
