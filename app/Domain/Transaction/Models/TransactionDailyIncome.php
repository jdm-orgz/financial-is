<?php

namespace App\Domain\Transaction\Models;

use App\Domain\Outlet\Models\Chair;
use Database\Factories\TransactionDailyIncomeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['transaction_id', 'chair_id', 'amount'])]
class TransactionDailyIncome extends Model
{
    /** @use HasFactory<TransactionDailyIncomeFactory> */
    use HasFactory, HasUuids;

    protected static function newFactory()
    {
        return TransactionDailyIncomeFactory::new();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    /**
     * Get the transaction that owns the daily income.
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get the chair for this daily income.
     */
    public function chair()
    {
        return $this->belongsTo(Chair::class);
    }
}
