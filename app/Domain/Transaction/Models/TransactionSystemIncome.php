<?php

namespace App\Domain\Transaction\Models;

use App\Domain\Outlet\Models\Chair;
use Database\Factories\TransactionSystemIncomeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['transaction_id', 'chair_id', 'amount'])]
class TransactionSystemIncome extends Model
{
    /** @use HasFactory<TransactionSystemIncomeFactory> */
    use HasFactory, HasUuids;

    protected static function newFactory()
    {
        return TransactionSystemIncomeFactory::new();
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
     * Get the transaction that owns the system income.
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get the chair for this system income.
     */
    public function chair()
    {
        return $this->belongsTo(Chair::class);
    }
}
