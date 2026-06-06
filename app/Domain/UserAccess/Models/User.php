<?php

namespace App\Domain\UserAccess\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Domain\Outlet\Models\LinkedOutletUser;
use App\Domain\Outlet\Models\Outlet;
use App\Traits\EncryptsId;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Lauthz\Facades\Enforcer;

#[Fillable(['role_id', 'username', 'name', 'email', 'password', 'is_active'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use EncryptsId, HasFactory, HasUuids, Notifiable, PasskeyAuthenticatable, SoftDeletes, TwoFactorAuthenticatable;

    protected static function newFactory()
    {
        return UserFactory::new();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Get the role associated with the user.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the outlets associated with the user.
     */
    public function outlets()
    {
        return $this->belongsToMany(Outlet::class, 'linked_outlets_users')
            ->using(LinkedOutletUser::class)
            ->withPivot(['id', 'is_active', 'deleted_at'])
            ->wherePivotNull('deleted_at')
            ->withTimestamps();
    }

    /**
     * Check if the user has permission to access a menu/action via Casbin.
     */
    public function hasPermissionTo(string $menu, string $action): bool
    {
        if (! $this->role) {
            return false;
        }

        return Enforcer::enforce($this->role->name, $menu, $action);
    }
}
