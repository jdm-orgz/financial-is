<?php

namespace App\Domain\Transaction\Repositories;

use App\Domain\Transaction\Models\Transaction;
use App\Enums\TransactionStatus;
use Illuminate\Pagination\LengthAwarePaginator;

interface TransactionRepositoryInterface
{
    /**
     * Get paginated transactions for an SPG user.
     */
    public function getPaginatedForSpg(string $spgUserId, int $perPage = 10, ?string $search = null, ?string $status = null): LengthAwarePaginator;

    /**
     * Get paginated transactions for a supervisor.
     */
    public function getPaginatedForSupervisor(string $supervisorId, int $perPage = 10, ?string $status = null): LengthAwarePaginator;

    /**
     * Get paginated transactions for admin.
     */
    public function getPaginatedForAdmin(int $perPage = 10, ?string $status = null): LengthAwarePaginator;

    /**
     * Get all paginated transactions (all statuses).
     */
    public function getPaginatedAll(int $perPage = 10, ?string $search = null, ?string $status = null): LengthAwarePaginator;

    /**
     * Find a transaction by ID with all relations.
     */
    public function findById(string $id): ?Transaction;

    /**
     * Create a new transaction.
     */
    public function create(array $data): Transaction;

    /**
     * Update a transaction's status with optional extra data.
     */
    public function updateStatus(string $id, TransactionStatus $status, array $extra = []): bool;

    /**
     * Check if a transaction exists for a given outlet and date.
     */
    public function existsForOutletAndDate(string $outletId, string $date, ?string $excludeId = null): bool;

    /**
     * Delete a transaction.
     */
    public function delete(string $id): bool;
}
