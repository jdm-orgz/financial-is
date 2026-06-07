<?php

namespace App\Domain\Outlet\Repositories;

use App\Domain\Outlet\Models\Chair;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentChairRepository implements ChairRepositoryInterface
{
    public function getPaginated(string $outletId, int $perPage = 10, ?string $search = null, ?string $sortBy = null, string $sortDirection = 'asc'): LengthAwarePaginator
    {
        $query = Chair::where('outlet_id', $outletId);

        if ($search) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        $validSortColumns = ['name', 'is_active', 'created_at'];
        if ($sortBy && in_array($sortBy, $validSortColumns)) {
            $query->orderBy($sortBy, $sortDirection === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function findById(string $id): ?Chair
    {
        return Chair::find($id);
    }

    public function create(array $data): Chair
    {
        return Chair::create($data);
    }

    public function update(string $id, array $data): bool
    {
        $chair = $this->findById($id);

        if (! $chair) {
            return false;
        }

        return $chair->update($data);
    }

    public function delete(string $id): bool
    {
        $chair = $this->findById($id);

        if (! $chair) {
            return false;
        }

        return $chair->delete();
    }
}
