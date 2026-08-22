<?php

namespace App\Domain\Transaction\Models;

use App\Domain\Outlet\Models\Outlet;
use App\Domain\UserAccess\Models\User;
use App\Enums\TransactionStatus;
use App\Traits\EncryptsId;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'outlet_id', 'date', 'status', 'spg_notes', 'supervisor_notes', 'admin_notes',
    'created_by', 'supervisor_actioned_by', 'supervisor_actioned_at',
    'admin_actioned_by', 'admin_actioned_at',
])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use EncryptsId, HasFactory, HasUuids, SoftDeletes;

    protected static function newFactory()
    {
        return TransactionFactory::new();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TransactionStatus::class,
            'date' => 'date',
            'supervisor_actioned_at' => 'datetime',
            'admin_actioned_at' => 'datetime',
        ];
    }

    /**
     * Get the outlet that owns the transaction.
     */
    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    /**
     * Get the user who created the transaction.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the supervisor who actioned the transaction.
     */
    public function supervisorActionedBy()
    {
        return $this->belongsTo(User::class, 'supervisor_actioned_by');
    }

    /**
     * Get the admin who actioned the transaction.
     */
    public function adminActionedBy()
    {
        return $this->belongsTo(User::class, 'admin_actioned_by');
    }

    /**
     * Get the daily incomes for the transaction.
     */
    public function dailyIncomes()
    {
        return $this->hasMany(TransactionDailyIncome::class);
    }

    /**
     * Get the replacement realizations for the transaction.
     */
    public function replacementRealizations()
    {
        return $this->hasMany(TransactionReplacementRealization::class);
    }

    /**
     * Get the transfer proofs for the transaction.
     */
    public function transferProofs()
    {
        return $this->hasMany(TransactionTransferProof::class);
    }

    /**
     * Get the system incomes for the transaction.
     */
    public function systemIncomes()
    {
        return $this->hasMany(TransactionSystemIncome::class);
    }
}
