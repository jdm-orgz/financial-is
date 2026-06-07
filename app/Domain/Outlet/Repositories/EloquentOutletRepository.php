<?php

namespace App\Domain\Outlet\Repositories;

use App\Domain\Outlet\Models\Outlet;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentOutletRepository implements OutletRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function getPaginated(int $perPage = 10, ?string $search = null, ?string $sortBy = null, string $sortDirection = 'asc'): LengthAwarePaginator
    {
        $query = Outlet::query();

        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('address', 'like', "%{$search}%");
        }

        if ($sortBy) {
            $query->orderBy($sortBy, $sortDirection === 'desc' ? 'desc' : 'asc');
        }

        return $query->withCount('chairs')->paginate($perPage)->withQueryString();
    }

    /**
     * {@inheritDoc}
     */
    public function findById(string $id): ?Outlet
    {
        return Outlet::query()->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): Outlet
    {
        return Outlet::query()->create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function update(string $id, array $data): bool
    {
        $outlet = $this->findById($id);

        if (! $outlet) {
            return false;
        }

        return $outlet->update($data);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $id): bool
    {
        $outlet = $this->findById($id);

        if (! $outlet) {
            return false;
        }

        return $outlet->delete();
    }
}
