<?php

namespace App\Http\Controllers\Transaction;

use App\Domain\Transaction\Repositories\TransactionRepositoryInterface;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
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
        $status = request('status');
        $perPage = (int) request('per_page', 10);

        $transactions = $this->transactionRepository->getPaginatedForSupervisor(
            auth()->id(),
            $perPage,
            $status
        );

        return Inertia::render('Supervisor/Transactions/Index', [
            'transactions' => $transactions,
            'filters' => request()->only(['status']),
            'per_page' => $perPage,
        ]);
    }

    /**
     * Display the specified transaction (read-only).
     */
    public function show(string $transactionId): Response
    {
        try {
            $decryptedId = (string) Crypt::decryptString($transactionId);
        } catch (DecryptException $e) {
            abort(404);
        }

        $transaction = $this->transactionRepository->findById($decryptedId);

        if (! $transaction) {
            abort(404);
        }

        // Verify supervisor has access to this outlet
        $supervisorOutletIds = auth()->user()->outlets()->pluck('outlets.id');
        if (! $supervisorOutletIds->contains($transaction->outlet_id)) {
            abort(403);
        }

        return Inertia::render('Supervisor/Transactions/Show', [
            'transaction' => $transaction,
        ]);
    }

    /**
     * Approve the transaction.
     */
    public function approve(string $transactionId): RedirectResponse
    {
        try {
            $decryptedId = (string) Crypt::decryptString($transactionId);
        } catch (DecryptException $e) {
            abort(404);
        }

        $transaction = $this->transactionRepository->findById($decryptedId);

        if (! $transaction) {
            abort(404);
        }

        if ($transaction->status !== TransactionStatus::Approval) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Transaction is not pending approval.']);

            return redirect()->back();
        }

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

        try {
            $decryptedId = (string) Crypt::decryptString($transactionId);
        } catch (DecryptException $e) {
            abort(404);
        }

        $transaction = $this->transactionRepository->findById($decryptedId);

        if (! $transaction) {
            abort(404);
        }

        if ($transaction->status !== TransactionStatus::Approval) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Transaction is not pending approval.']);

            return redirect()->back();
        }

        $this->transactionRepository->updateStatus($decryptedId, TransactionStatus::Correction, [
            'supervisor_actioned_by' => auth()->id(),
            'supervisor_actioned_at' => now(),
            'supervisor_notes' => $validated['supervisor_notes'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Transaction rejected successfully.']);

        return redirect()->route('supervisor.transactions.index');
    }
}
