<?php

namespace App\Http\Controllers\Transaction;

use App\Domain\Transaction\Repositories\TransactionDailyIncomeRepositoryInterface;
use App\Domain\Transaction\Repositories\TransactionRepositoryInterface;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\StoreTransactionDailyIncomeRequest;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;

class TransactionDailyIncomeController extends Controller
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly TransactionDailyIncomeRepositoryInterface $dailyIncomeRepository
    ) {}

    /**
     * Upsert daily incomes for a transaction.
     */
    public function upsert(StoreTransactionDailyIncomeRequest $request, string $transactionId): RedirectResponse
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

        // Decrypt chair_ids
        $items = collect($request->validated('incomes'))->map(function ($item) {
            try {
                return [
                    'chair_id' => (string) Crypt::decryptString($item['chair_id']),
                    'amount' => $item['amount'],
                ];
            } catch (DecryptException $e) {
                abort(404);
            }
        })->all();

        $this->dailyIncomeRepository->upsertForTransaction($decryptedId, $items);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Daily income saved successfully.']);

        return redirect()->back();
    }
}
