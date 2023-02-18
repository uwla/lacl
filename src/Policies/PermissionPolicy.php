<?php

namespace Uwla\Lacl\Policies;

use Uwla\Lacl\Models\Permission;
use Uwla\Lacl\Contracts\ResourcePolicy;
use Uwla\Lacl\Traits\ResourcePolicy as HandlesPermissions;
use Illuminate\Auth\Access\HandlesAuthorization;

class PermissionPolicy implements ResourcePolicy
{
    use HandlesAuthorization, HandlesPermissions;

    public function getResourceModel()
    {
        return Permission::class;
    }
}
