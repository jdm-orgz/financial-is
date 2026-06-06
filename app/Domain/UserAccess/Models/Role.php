<?php

namespace App\Domain\UserAccess\Models;

use App\Traits\EncryptsId;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'description', 'is_active'])]
class Role extends Model
{
    public const AVAILABLE_PERMISSIONS = [
        'master/*' => 'Master Data',
        'configuration/*' => 'Configuration',
        'transaction/approval/*' => 'Transaction Approval',
        'transaction/*' => 'Transaction Management',
    ];

    /** @use HasFactory<RoleFactory> */
    use EncryptsId, HasFactory, SoftDeletes;

    protected static function newFactory()
    {
        return RoleFactory::new();
    }

    /**
     * Get the users associated with the role.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
