<?php

namespace App\Http\Controllers\Transaction;

use App\Domain\Transaction\Repositories\TransactionReplacementRealizationRepositoryInterface;
use App\Domain\Transaction\Repositories\TransactionRepositoryInterface;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\StoreTransactionReplacementRealizationRequest;
use App\Http\Requests\Transaction\UpdateTransactionReplacementRealizationRequest;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class TransactionReplacementRealizationController extends Controller
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly TransactionReplacementRealizationRepositoryInterface $realizationRepository
    ) {}

    /**
     * Store a newly created replacement realization.
     */
    public function store(StoreTransactionReplacementRealizationRequest $request, string $transactionId): RedirectResponse
    {
        try {
            $decryptedTransactionId = (string) Crypt::decryptString($transactionId);
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

        try {
            $decryptedProblemChairId = (string) Crypt::decryptString($request->validated('problem_chair_id'));
            $decryptedReplacementChairId = (string) Crypt::decryptString($request->validated('replacement_chair_id'));
        } catch (DecryptException $e) {
            abort(404);
        }

        $timestamp = now()->format('YmdHis');

        $data = [
            'transaction_id' => $decryptedTransactionId,
            'problem_chair_id' => $decryptedProblemChairId,
            'replacement_chair_id' => $decryptedReplacementChairId,
            'payment_method' => $request->validated('payment_method'),
            'amount' => $request->validated('amount'),
        ];

        if ($request->hasFile('proof_image')) {
            $data['proof_image_path'] = $request->file('proof_image')
                ->storeAs('proofs/images', "{$decryptedTransactionId}_failed_{$timestamp}.".$request->file('proof_image')->extension(), 'public');
        }

        if ($request->hasFile('proof_video')) {
            $data['proof_video_path'] = $request->file('proof_video')
                ->storeAs('proofs/videos', "{$decryptedTransactionId}_success_{$timestamp}.".$request->file('proof_video')->extension(), 'public');
        }

        $this->realizationRepository->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Replacement realization added successfully.']);

        return redirect()->back();
    }

    /**
     * Update the specified replacement realization.
     */
    public function update(UpdateTransactionReplacementRealizationRequest $request, string $transactionId, string $realizationId): RedirectResponse
    {
        try {
            $decryptedTransactionId = (string) Crypt::decryptString($transactionId);
            $decryptedRealizationId = (string) Crypt::decryptString($realizationId);
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

        $realization = $this->realizationRepository->findById($decryptedRealizationId);

        if (! $realization || $realization->transaction_id !== $decryptedTransactionId) {
            abort(404);
        }

        $data = [];
        $validated = $request->validated();

        if (isset($validated['problem_chair_id'])) {
            try {
                $data['problem_chair_id'] = (string) Crypt::decryptString($validated['problem_chair_id']);
            } catch (DecryptException $e) {
                abort(404);
            }
        }

        if (isset($validated['replacement_chair_id'])) {
            try {
                $data['replacement_chair_id'] = (string) Crypt::decryptString($validated['replacement_chair_id']);
            } catch (DecryptException $e) {
                abort(404);
            }
        }

        if (isset($validated['payment_method'])) {
            $data['payment_method'] = $validated['payment_method'];
        }

        if (isset($validated['amount'])) {
            $data['amount'] = $validated['amount'];
        }

        $timestamp = now()->format('YmdHis');

        if ($request->hasFile('proof_image')) {
            if ($realization->proof_image_path) {
                Storage::disk('public')->delete($realization->proof_image_path);
            }
            $data['proof_image_path'] = $request->file('proof_image')
                ->storeAs('proofs/images', "{$decryptedTransactionId}_failed_{$timestamp}.".$request->file('proof_image')->extension(), 'public');
        }

        if ($request->hasFile('proof_video')) {
            if ($realization->proof_video_path) {
                Storage::disk('public')->delete($realization->proof_video_path);
            }
            $data['proof_video_path'] = $request->file('proof_video')
                ->storeAs('proofs/videos', "{$decryptedTransactionId}_success_{$timestamp}.".$request->file('proof_video')->extension(), 'public');
        }

        $this->realizationRepository->update($decryptedRealizationId, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Replacement realization updated successfully.']);

        return redirect()->back();
    }

    /**
     * Remove the specified replacement realization.
     */
    public function destroy(string $transactionId, string $realizationId): RedirectResponse
    {
        try {
            $decryptedTransactionId = (string) Crypt::decryptString($transactionId);
            $decryptedRealizationId = (string) Crypt::decryptString($realizationId);
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

        $realization = $this->realizationRepository->findById($decryptedRealizationId);

        if (! $realization || $realization->transaction_id !== $decryptedTransactionId) {
            abort(404);
        }

        // Delete files from storage
        if ($realization->proof_image_path) {
            Storage::disk('public')->delete($realization->proof_image_path);
        }
        if ($realization->proof_video_path) {
            Storage::disk('public')->delete($realization->proof_video_path);
        }

        $this->realizationRepository->delete($decryptedRealizationId);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Replacement realization deleted successfully.']);

        return redirect()->back();
    }
}
