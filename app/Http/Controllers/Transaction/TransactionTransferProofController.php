<?php

namespace App\Http\Controllers\Transaction;

use App\Domain\Transaction\Repositories\TransactionRepositoryInterface;
use App\Domain\Transaction\Repositories\TransactionTransferProofRepositoryInterface;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\StoreTransactionTransferProofRequest;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class TransactionTransferProofController extends Controller
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly TransactionTransferProofRepositoryInterface $transferProofRepository
    ) {}

    /**
     * Store a newly created transfer proof.
     */
    public function store(StoreTransactionTransferProofRequest $request, string $transactionId): RedirectResponse
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

        if (! in_array($transaction->status, [TransactionStatus::Draft, TransactionStatus::Correction])) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Transaction is not in an editable status.']);

            return redirect()->back();
        }

        $timestamp = now()->format('YmdHis');
        $path = $request->file('proof_image')
            ->storeAs('proofs/transfers', "{$decryptedId}_transfer-proof_{$timestamp}.".$request->file('proof_image')->extension(), 'public');

        $this->transferProofRepository->create([
            'transaction_id' => $decryptedId,
            'proof_image_path' => $path,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Transfer proof uploaded successfully.']);

        return redirect()->back();
    }

    /**
     * Remove the specified transfer proof.
     */
    public function destroy(string $transactionId, string $proofId): RedirectResponse
    {
        try {
            $decryptedTransactionId = (string) Crypt::decryptString($transactionId);
            $decryptedProofId = (string) Crypt::decryptString($proofId);
        } catch (DecryptException $e) {
            abort(404);
        }

        $transaction = $this->transactionRepository->findById($decryptedTransactionId);

        if (! $transaction || $transaction->created_by !== auth()->id()) {
            abort(404);
        }

        if (! in_array($transaction->status, [TransactionStatus::Draft, TransactionStatus::Correction])) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Transaction is not in an editable status.']);

            return redirect()->back();
        }

        $proof = $this->transferProofRepository->findById($decryptedProofId);

        if (! $proof || $proof->transaction_id !== $decryptedTransactionId) {
            abort(404);
        }

        Storage::disk('public')->delete($proof->proof_image_path);
        $this->transferProofRepository->delete($decryptedProofId);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Transfer proof deleted successfully.']);

        return redirect()->back();
    }
}
