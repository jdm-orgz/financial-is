<?php

namespace App\Domain\Outlet\Models;

use App\Traits\EncryptsId;
use Database\Factories\ChairFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['outlet_id', 'name', 'is_active'])]
class Chair extends Model
{
    /** @use HasFactory<ChairFactory> */
    use EncryptsId, HasFactory, HasUuids, SoftDeletes;

    protected static function newFactory()
    {
        return ChairFactory::new();
    }

    /**
     * Get the outlet that owns the chair.
     */
    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }
}
