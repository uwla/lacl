<?php

namespace Tests\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Tests\App\Database\Factories\UserFactory;
use Uwla\Lacl\Contracts\HasPermissionContract;
use Uwla\Lacl\Contracts\HasRoleContract;
use Uwla\Lacl\Traits\HasRole;

class User extends Authenticatable implements HasRoleContract, HasPermissionContract
{
    use HasApiTokens, HasFactory, HasRole, HasCustomAclModels {
        HasCustomAclModels::Permission insteadof HasRole;
        HasCustomAclModels::Role insteadof HasRole;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return UserFactory::new();
    }
}
