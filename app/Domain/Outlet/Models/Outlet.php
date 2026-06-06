<?php

namespace App\Domain\Outlet\Models;

use App\Domain\UserAccess\Models\User;
use App\Traits\EncryptsId;
use Database\Factories\OutletFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'address', 'latitude', 'longitude', 'is_active'])]
class Outlet extends Model
{
    /** @use HasFactory<OutletFactory> */
    use EncryptsId, HasFactory, HasUuids, SoftDeletes;

    protected static function newFactory()
    {
        return OutletFactory::new();
    }

    /**
     * Get the users associated with the outlet.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'linked_outlets_users')
            ->using(LinkedOutletUser::class)
            ->withTimestamps();
    }
}
