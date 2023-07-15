<?php

namespace Tests\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Tests\App\Models\User;
use Uwla\Lacl\Contracts\ResourcePolicy;
use Uwla\Lacl\Traits\ResourcePolicy as HandlesPermissions;

class UserPolicy implements ResourcePolicy
{
    use HandlesAuthorization, HandlesPermissions;

    public function getResourceModel()
    {
        return User::class;
    }
}
