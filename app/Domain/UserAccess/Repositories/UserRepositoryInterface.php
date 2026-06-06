<?php

namespace App\Domain\UserAccess\Repositories;

use App\Domain\UserAccess\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    public function getPaginated(int $perPage = 10, ?string $search = null, ?string $sortBy = null, string $sortDirection = 'asc'): LengthAwarePaginator;

    public function findById(string $id): ?User;

    public function findByEmail(string $email): ?User;

    public function create(array $data): User;

    public function update(string $id, array $data): bool;

    public function delete(string $id): bool;
}
