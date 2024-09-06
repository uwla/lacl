<?php

namespace Uwla\Lacl\Traits;

use Uwla\Lacl\Models\Permission;
use Uwla\Lacl\Models\Role;

Trait CustomAclModels
{
    /**
     * Get the class of Role
     *
     * @return string
     */
    protected static function Role(): string
    {
        return Role::class;
    }

    /**
     * Get the class of Permission
     *
     * @return string
     */
    protected static function Permission(): string
    {
        return Permission::class;
    }
}