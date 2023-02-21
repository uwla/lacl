<?php

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Uwla\Lacl\Contracts\Permissionable as PermissionableContract;
use Uwla\Lacl\Traits\Permissionable;

class Article extends Model implements PermissionableContract
{
    use HasFactory, Permissionable;

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return ArticleFactory::new();
    }
}
