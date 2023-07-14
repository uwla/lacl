<?php

namespace Uwla\Lacl\Models;

use Uwla\Lacl\Contracts\HasPermissionContract;
use Uwla\Lacl\Traits\PermissionableHasRole;
use Uwla\Lacl\Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model implements HasPermissionContract
{
    use HasFactory, PermissionableHasRole;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    public $fillable = [ 'name', 'description' ];

    /**
     * Create a new factory instance for the model.
     * This is used for testing. End-users are encouraged to change it.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return RoleFactory::new();
    }
}
