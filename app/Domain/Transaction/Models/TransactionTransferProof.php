<?php

namespace App\Domain\Transaction\Models;

use Database\Factories\TransactionTransferProofFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['transaction_id', 'proof_image_path'])]
class TransactionTransferProof extends Model
{
    /** @use HasFactory<TransactionTransferProofFactory> */
    use HasFactory, HasUuids;

    protected static function newFactory()
    {
        return TransactionTransferProofFactory::new();
    }

    /**
     * Get the transaction that owns the transfer proof.
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
