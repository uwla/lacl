<?php

namespace Uwla\Lacl\Policies;

use Uwla\Lacl\Models\Role;
use Uwla\Lacl\Contracts\ResourcePolicy;
use Uwla\Lacl\Traits\ResourcePolicy as HandlesPermissions;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy implements ResourcePolicy
{
    use HandlesAuthorization, HandlesPermissions;

    public function getResourceModel()
    {
        return Role::class;
    }
}
