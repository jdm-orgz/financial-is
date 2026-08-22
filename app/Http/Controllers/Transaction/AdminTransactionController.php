<?php

namespace App\Http\Controllers\Transaction;

use App\Domain\Transaction\Actions\CalculateVarianceAction;
use App\Domain\Transaction\Repositories\TransactionRepositoryInterface;
use App\Domain\Transaction\Repositories\TransactionSystemIncomeRepositoryInterface;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\StoreTransactionSystemIncomeRequest;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use Inertia\Response;

class AdminTransactionController extends Controller
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly TransactionSystemIncomeRepositoryInterface $systemIncomeRepository,
        private readonly CalculateVarianceAction $calculateVarianceAction
    ) {}

    /**
     * Display a listing of transactions for comparison.
     */
    public function index(): Response
    {
        $status = request('status');
        $perPage = (int) request('per_page', 10);

        $transactions = $this->transactionRepository->getPaginatedForAdmin($perPage, $status);

        return Inertia::render('Admin/Transactions/Index', [
            'transactions' => $transactions,
            'filters' => request()->only(['status']),
            'per_page' => $perPage,
        ]);
    }

    /**
     * Display all transactions (all statuses).
     */
    public function all(): Response
    {
        $search = request('search');
        $status = request('status');
        $perPage = (int) request('per_page', 10);

        $transactions = $this->transactionRepository->getPaginatedAll($perPage, $search, $status);

        return Inertia::render('Admin/Transactions/All', [
            'transactions' => $transactions,
            'filters' => request()->only(['search', 'status']),
            'per_page' => $perPage,
            'statusOptions' => TransactionStatus::options(),
        ]);
    }

    /**
     * Show the comparison form (system income input).
     */
    public function showCompare(string $transactionId): Response
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

        if ($transaction->status !== TransactionStatus::Comparing) {
            abort(403);
        }

        $chairs = $transaction->outlet->chairs()->where('is_active', '1')->get();

        return Inertia::render('Admin/Transactions/Compare', [
            'transaction' => $transaction,
            'chairs' => $chairs,
        ]);
    }

    /**
     * Store system incomes and move to compared status.
     */
    public function storeSystemIncome(StoreTransactionSystemIncomeRequest $request, string $transactionId): RedirectResponse
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

        if ($transaction->status !== TransactionStatus::Comparing) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Transaction is not in comparing status.']);

            return redirect()->back();
        }

        // Decrypt chair_ids
        $items = collect($request->validated('system_incomes'))->map(function ($item) {
            try {
                return [
                    'chair_id' => (string) Crypt::decryptString($item['chair_id']),
                    'amount' => $item['amount'],
                ];
            } catch (DecryptException $e) {
                abort(404);
            }
        })->all();

        $this->systemIncomeRepository->upsertForTransaction($decryptedId, $items);

        $this->transactionRepository->updateStatus($decryptedId, TransactionStatus::Compared, [
            'admin_actioned_by' => auth()->id(),
            'admin_actioned_at' => now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'System data saved successfully.']);

        return redirect()->route('admin.transactions.result', ['transaction' => $transactionId]);
    }

    /**
     * Show the comparison result.
     */
    public function showResult(string $transactionId): Response
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

        $comparison = $this->calculateVarianceAction->execute($transaction);

        return Inertia::render('Admin/Transactions/Result', [
            'transaction' => $transaction,
            'comparison' => $comparison,
        ]);
    }

    /**
     * Approve the transaction (final).
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

        if ($transaction->status !== TransactionStatus::Compared) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Transaction is not in an approvable status.']);

            return redirect()->back();
        }

        $this->transactionRepository->updateStatus($decryptedId, TransactionStatus::Done);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Transaction completed successfully.']);

        return redirect()->route('admin.transactions.index');
    }

    /**
     * Reject the transaction (send back to correction).
     */
    public function reject(Request $request, string $transactionId): RedirectResponse
    {
        $validated = $request->validate([
            'admin_notes' => ['required', 'string', 'max:1000'],
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

        if ($transaction->status !== TransactionStatus::Compared) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Transaction is not in a rejectable status.']);

            return redirect()->back();
        }

        $this->transactionRepository->updateStatus($decryptedId, TransactionStatus::Correction, [
            'admin_notes' => $validated['admin_notes'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Transaction rejected successfully.']);

        return redirect()->route('admin.transactions.index');
    }
}
