<?php

namespace Uwla\Lacl\Traits;

use Uwla\Lacl\Models\Permission;
use Uwla\Lacl\Models\Role;

Trait CustomAclModels
{
    /**
      * Get the class of Role
      *
      * @return class
      */
    protected static function Role()
    {
        return Role::class;
    }

    /**
      * Get the class of Permission
      *
      * @return class
      */
    protected static function Permission()
    {
        return Permission::class;
    }
}
