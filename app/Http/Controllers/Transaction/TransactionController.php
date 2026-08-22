<?php

namespace App\Http\Controllers\Transaction;

use App\Domain\Outlet\Repositories\ChairRepositoryInterface;
use App\Domain\Transaction\Repositories\TransactionRepositoryInterface;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly ChairRepositoryInterface $chairRepository
    ) {}

    /**
     * Display a listing of the SPG's transactions.
     */
    public function index(): Response
    {
        $search = request('search');
        $status = request('status');
        $perPage = (int) request('per_page', 10);

        $transactions = $this->transactionRepository->getPaginatedForSpg(
            auth()->id(),
            $perPage,
            $search,
            $status
        );

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'filters' => request()->only(['search', 'status']),
            'per_page' => $perPage,
            'statusOptions' => TransactionStatus::options(),
        ]);
    }

    /**
     * Show the form for creating a new transaction.
     */
    public function create(): Response
    {
        $outlets = auth()->user()->outlets()->where('linked_outlets_users.is_active', '1')->get();

        return Inertia::render('Transactions/Create', [
            'outlets' => $outlets,
        ]);
    }

    /**
     * Store a newly created transaction.
     */
    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $decryptedOutletId = $request->decryptedOutletId();

        if (! $decryptedOutletId) {
            abort(404);
        }

        // Check unique constraint
        if ($this->transactionRepository->existsForOutletAndDate($decryptedOutletId, $request->validated('date'))) {
            return redirect()->back()->withErrors([
                'date' => 'Transaction for this outlet and date already exists.',
            ]);
        }

        $transaction = $this->transactionRepository->create([
            'outlet_id' => $decryptedOutletId,
            'date' => $request->validated('date'),
            'status' => TransactionStatus::Draft,
            'created_by' => auth()->id(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Transaction created successfully.']);

        return redirect()->route('transactions.show', ['transaction' => Crypt::encryptString($transaction->id)]);
    }

    /**
     * Display the specified transaction.
     */
    public function show(string $transactionId): Response
    {
        try {
            $decryptedId = (string) Crypt::decryptString($transactionId);
        } catch (DecryptException $e) {
            abort(404);
        }

        $transaction = $this->transactionRepository->findById($decryptedId);

        if (! $transaction || $transaction->created_by !== auth()->id()) {
            abort(404);
        }

        $chairs = $transaction->outlet->chairs()->where('is_active', '1')->get();

        return Inertia::render('Transactions/Show', [
            'transaction' => $transaction,
            'chairs' => $chairs,
        ]);
    }

    /**
     * Submit the transaction for approval.
     */
    public function submit(string $transactionId): RedirectResponse
    {
        try {
            $decryptedId = (string) Crypt::decryptString($transactionId);
        } catch (DecryptException $e) {
            abort(404);
        }

        $transaction = $this->transactionRepository->findById($decryptedId);

        if (! $transaction || $transaction->created_by !== auth()->id()) {
            abort(404);
        }

        // Must be in draft or correction status
        if (! in_array($transaction->status, [TransactionStatus::Draft, TransactionStatus::Correction])) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Transaction is not in a submittable status.']);

            return redirect()->back();
        }

        // Validate: all chairs must have daily income entries
        $outletChairIds = $transaction->outlet->chairs()->where('is_active', '1')->pluck('id');
        $filledChairIds = $transaction->dailyIncomes->pluck('chair_id');

        if ($outletChairIds->diff($filledChairIds)->isNotEmpty()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'All chairs must have daily income data.']);

            return redirect()->back();
        }

        // Validate: must have at least one transfer proof
        if ($transaction->transferProofs->isEmpty()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'At least one transfer proof is required.']);

            return redirect()->back();
        }

        $this->transactionRepository->updateStatus($decryptedId, TransactionStatus::Approval);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Transaction submitted for approval successfully.']);

        return redirect()->route('transactions.index');
    }

    /**
     * Remove the specified transaction.
     */
    public function destroy(string $transactionId): RedirectResponse
    {
        try {
            $decryptedId = (string) Crypt::decryptString($transactionId);
        } catch (DecryptException $e) {
            abort(404);
        }

        $transaction = $this->transactionRepository->findById($decryptedId);

        if (! $transaction || $transaction->created_by !== auth()->id()) {
            abort(404);
        }

        if ($transaction->status !== TransactionStatus::Draft) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Only draft transactions can be deleted.']);

            return redirect()->back();
        }

        $this->transactionRepository->delete($decryptedId);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Transaction deleted successfully.']);

        return redirect()->route('transactions.index');
    }
}
