<?php

namespace Tests\App\Models;

use Uwla\Lacl\Models\Permission as BasePermission;
use Tests\App\Database\Factories\PermissionFactory;

class Permission extends BasePermission
{
    use HasCustomAclModels;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    public $fillable = [
        'description',
        'model_type',
        'model_id',
        'name',
    ];

    /**
     * Create a new factory instance for the model.
     * This is used for testing. End-users are encouraged to change it.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return PermissionFactory::new();
    }
}