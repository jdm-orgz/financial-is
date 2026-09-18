<?php

namespace App\Http\Controllers\Transaction;

use App\Domain\Transaction\Repositories\TransactionRepositoryInterface;
use App\Domain\UserAccess\Models\User;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupervisorTransactionController extends Controller
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository
    ) {}

    /**
     * Display a listing of transactions awaiting supervisor approval.
     */
    public function index(): Response
    {
        $status = request('status', 'approval');
        $search = request('search');
        $sortBy = request('sort_by');
        $sortDirection = request('sort_direction', 'asc');
        $startDate = request('start_date', now()->format('Y-m-d'));
        $endDate = request('end_date', now()->format('Y-m-d'));
        $spgUsername = request('spg_username');
        $decryptedSpgId = null;

        if ($spgUsername && $spgUsername !== 'all') {
            $spgUser = User::where('username', $spgUsername)->first();
            if ($spgUser) {
                $decryptedSpgId = $spgUser->id;
            }
        }

        $perPage = (int) request('per_page', 10);
        $user = auth()->user();
        $isSuperAdmin = $user->role->name === 'super_admin';
        $supervisorId = $isSuperAdmin ? null : $user->id;

        $transactions = $this->transactionRepository->getPaginatedForSupervisor(
            $supervisorId,
            $perPage,
            $search,
            $status,
            $sortBy,
            $sortDirection,
            $startDate,
            $endDate,
            $decryptedSpgId
        );

        $spgsQuery = User::whereHas('role', function ($q) {
            $q->where('name', 'spg');
        });

        if (! $isSuperAdmin) {
            $spgsQuery->whereHas('outlets', function ($q) use ($supervisorId) {
                $q->whereHas('users', function ($q2) use ($supervisorId) {
                    $q2->where('users.id', $supervisorId);
                });
            });
        }

        $spgs = $spgsQuery->get(['id', 'name', 'username']);

        return Inertia::render('Supervisor/Transactions/Index', [
            'transactions' => $transactions,
            'filters' => array_merge(request()->only(['search', 'sort_by', 'sort_direction', 'spg_username']), [
                'status' => $status,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]),
            'per_page' => $perPage,
            'statusOptions' => TransactionStatus::options(),
            'spgs' => $spgs,
        ]);
    }

    /**
     * Display the specified transaction (read-only).
     */
    public function show(string $transactionId): Response
    {
        $transaction = request()->attributes->get('resolved_transaction');

        return Inertia::render('Supervisor/Transactions/Show', [
            'transaction' => $transaction,
        ]);
    }

    /**
     * Approve the transaction.
     */
    public function approve(string $transactionId): RedirectResponse
    {
        $decryptedId = request()->attributes->get('decrypted_transaction_id');

        $this->transactionRepository->updateStatus($decryptedId, TransactionStatus::Comparing, [
            'supervisor_actioned_by' => auth()->id(),
            'supervisor_actioned_at' => now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Transaction approved successfully.']);

        return redirect()->route('supervisor.transactions.index');
    }

    /**
     * Reject the transaction.
     */
    public function reject(Request $request, string $transactionId): RedirectResponse
    {
        $validated = $request->validate([
            'supervisor_notes' => ['required', 'string', 'max:1000'],
        ]);

        $decryptedId = request()->attributes->get('decrypted_transaction_id');

        $this->transactionRepository->updateStatus($decryptedId, TransactionStatus::Correction, [
            'supervisor_actioned_by' => auth()->id(),
            'supervisor_actioned_at' => now(),
            'supervisor_notes' => $validated['supervisor_notes'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Transaction rejected successfully.']);

        return redirect()->route('supervisor.transactions.index');
    }
}
