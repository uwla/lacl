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

    // /**
    //  * Determine whether the user can update the model.
    //  *
    //  * @param  User  $user
    //  * @param  \Illuminate\Database\Eloquent\Model  $model
    //  * @return \Illuminate\Auth\Access\Response|bool
    //  */
    // public function view(User $user, User $model)
    // {
    //     echo "e ai irmao\n";
    //     ob_flush();
    //     return $this->userHasPermission($user, ['update', 'updateAny'], [$model->id, null]);
    // }
}
