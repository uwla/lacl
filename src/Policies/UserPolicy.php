<?php

namespace Uwla\Lacl\Policies;

use Uwla\Lacl\Models\User;
use Uwla\Lacl\Contracts\ResourcePolicy;
use Uwla\Lacl\Traits\ResourcePolicy as HandlesPermissions;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy implements ResourcePolicy
{
    use HandlesAuthorization, HandlesPermissions;

    public function getResourceModel()
    {
        return User::class;
    }
}
