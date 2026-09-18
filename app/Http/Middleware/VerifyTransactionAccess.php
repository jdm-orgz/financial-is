<?php

namespace App\Http\Middleware;

use App\Domain\Transaction\Repositories\TransactionRepositoryInterface;
use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

class VerifyTransactionAccess
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $roleContext, ...$allowedStatuses): Response
    {
        $transactionId = $request->route('transaction');

        if (! $transactionId) {
            return $next($request);
        }

        try {
            $decryptedId = (string) Crypt::decryptString($transactionId);
        } catch (DecryptException $e) {
            abort(404);
        }

        $transaction = $this->transactionRepository->findById($decryptedId);

        if (! $transaction) {
            abort(404);
        }

        // Role Context Ownership Check
        if ($roleContext === 'spg') {
            if ($transaction->created_by !== auth()->id()) {
                abort(404);
            }
        } elseif ($roleContext === 'spv') {
            $supervisorOutletIds = auth()->user()->outlets()->pluck('outlets.id');
            if (! $supervisorOutletIds->contains($transaction->outlet_id)) {
                abort(403);
            }
        } elseif ($roleContext === 'admin') {
            // Admins can access any transaction, no ownership check needed
        } else {
            abort(500, 'Invalid role context in middleware.');
        }

        // Status Check
        if (! empty($allowedStatuses)) {
            if (! in_array($transaction->status->value, $allowedStatuses)) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Transaction status is invalid for this action.'], 403);
                }

                session()->flash('toast', ['type' => 'error', 'message' => 'Transaction is not in a valid status for this action.']);

                return redirect()->back();
            }
        }

        // Bind the transaction directly onto the request to avoid re-fetching in the controller.
        $request->attributes->set('resolved_transaction', $transaction);
        $request->attributes->set('decrypted_transaction_id', $decryptedId);

        return $next($request);
    }
}
