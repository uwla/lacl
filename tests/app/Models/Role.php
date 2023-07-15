<?php

namespace Tests\App\Models;

use Uwla\Lacl\Models\Role as BaseRole;
use Tests\App\Database\Factories\RoleFactory;

class Role extends BaseRole
{
    use HasCustomAclModels;

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