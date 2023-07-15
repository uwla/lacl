<?php

namespace Tests\App\Models;

Trait HasCustomAclModels
{
    /**
      * Get the class of Role
      *
      * @return string
      */
    protected static function Role()
    {
        return Role::class;
    }

    /**
      * Get the class of Permission
      *
      * @return string
      */
    protected static function Permission()
    {
        return Permission::class;
    }
}