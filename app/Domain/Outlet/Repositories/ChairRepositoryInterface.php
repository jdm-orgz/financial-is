<?php

namespace App\Domain\Outlet\Repositories;

use App\Domain\Outlet\Models\Chair;
use Illuminate\Pagination\LengthAwarePaginator;

interface ChairRepositoryInterface
{
    /**
     * Get paginated chairs for an outlet.
     */
    public function getPaginated(string $outletId, int $perPage = 10, ?string $search = null, ?string $sortBy = null, string $sortDirection = 'asc'): LengthAwarePaginator;

    /**
     * Get a chair by ID.
     */
    public function findById(string $id): ?Chair;

    /**
     * Create a new chair.
     */
    public function create(array $data): Chair;

    /**
     * Update an existing chair.
     */
    public function update(string $id, array $data): bool;

    /**
     * Delete a chair.
     */
    public function delete(string $id): bool;
}
