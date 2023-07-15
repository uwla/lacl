<?php

namespace Tests\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tests\App\Database\Factories\ArticleFactory;
use Uwla\Lacl\Traits\Permissionable;

class Article extends Model
{
    use HasFactory, Permissionable, HasCustomAclModels  {
        HasCustomAclModels::Permission insteadof Permissionable;
        HasCustomAclModels::Role insteadof Permissionable;
    }

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
