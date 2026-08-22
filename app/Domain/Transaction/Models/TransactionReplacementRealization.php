<?php

namespace App\Domain\Transaction\Models;

use App\Domain\Outlet\Models\Chair;
use App\Enums\PaymentMethod;
use Database\Factories\TransactionReplacementRealizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'transaction_id', 'problem_chair_id', 'replacement_chair_id',
    'payment_method', 'amount', 'proof_image_path', 'proof_video_path',
])]
class TransactionReplacementRealization extends Model
{
    /** @use HasFactory<TransactionReplacementRealizationFactory> */
    use HasFactory, HasUuids;

    protected static function newFactory()
    {
        return TransactionReplacementRealizationFactory::new();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'amount' => 'decimal:2',
        ];
    }

    /**
     * Get the transaction that owns the realization.
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get the problem chair.
     */
    public function problemChair()
    {
        return $this->belongsTo(Chair::class, 'problem_chair_id');
    }

    /**
     * Get the replacement chair.
     */
    public function replacementChair()
    {
        return $this->belongsTo(Chair::class, 'replacement_chair_id');
    }
}
