<?php

namespace Tests\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Tests\App\Models\Role;
use Uwla\Lacl\Contracts\ResourcePolicy;
use Uwla\Lacl\Traits\ResourcePolicy as HandlesPermissions;

class RolePolicy implements ResourcePolicy
{
    use HandlesAuthorization, HandlesPermissions;

    public function getResourceModel()
    {
        return Role::class;
    }
}
