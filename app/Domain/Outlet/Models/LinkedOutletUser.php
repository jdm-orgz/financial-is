<?php

namespace App\Domain\Outlet\Models;

use App\Domain\UserAccess\Models\User;
use App\Traits\EncryptsId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['outlet_id', 'user_id', 'is_active'])]
class LinkedOutletUser extends Pivot
{
    use EncryptsId, HasUuids, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'linked_outlets_users';

    /**
     * Get the user associated with the pivot.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the outlet associated with the pivot.
     */
    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }
}
