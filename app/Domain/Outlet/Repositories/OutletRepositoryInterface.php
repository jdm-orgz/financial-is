<?php

namespace App\Domain\Outlet\Repositories;

use App\Domain\Outlet\Models\Outlet;
use Illuminate\Pagination\LengthAwarePaginator;

interface OutletRepositoryInterface
{
    /**
     * Get paginated outlets.
     */
    public function getPaginated(int $perPage = 10, ?string $search = null, ?string $sortBy = null, string $sortDirection = 'asc'): LengthAwarePaginator;

    /**
     * Get a outlet by ID.
     *
     * @param  int  $id
     */
    public function findById(string $id): ?Outlet;

    /**
     * Create a new outlet.
     */
    public function create(array $data): Outlet;

    /**
     * Update an existing outlet.
     *
     * @param  int  $id
     */
    public function update(string $id, array $data): bool;

    /**
     * Delete a outlet.
     *
     * @param  int  $id
     */
    public function delete(string $id): bool;
}
