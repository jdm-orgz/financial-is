<?php

namespace App\Domain\Outlet\Models;

use App\Traits\EncryptsId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['outlet_id', 'prefix', 'last_counter'])]
class ChairPrefix extends Model
{
    use EncryptsId, HasUuids;

    /**
     * Get the outlet that owns the prefix.
     */
    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }
}
