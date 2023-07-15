<?php

namespace Tests\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Tests\App\Models\Permission;
use Uwla\Lacl\Contracts\ResourcePolicy;
use Uwla\Lacl\Traits\ResourcePolicy as HandlesPermissions;

class PermissionPolicy implements ResourcePolicy
{
    use HandlesAuthorization, HandlesPermissions;

    public function getResourceModel()
    {
        return Permission::class;
    }
}
