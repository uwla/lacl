<?php

namespace Uwla\Lacl\Traits;

use Illuminate\Foundation\Auth\User;
use Illuminate\Database\Eloquent\Model;

Trait ResourcePolicy
{
    /**
     * Determine whether the user has any of the given permissions associated
     * with the given models.
     *
     * @param  User          $user          the authenticated user
     * @param  array<string> $permissions   the name of the permissions
     * @param  array<int>    $models        the id of the models
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function userHasPermission(User $user, $permissions, $models): bool
    {
        // the model is the class name and the namespace
        // for example: User
        $model = $this->getResourceModel();

        // the class name is the last word delimited by backslashes
        $array = explode('\\', $model);
        $modelClassName = end($array);

        // the class name is a prefix for the permissions
        $prefix = strtolower($modelClassName);
        foreach ($permissions as $i => $permission)
            $permissions[$i] = $prefix . "." . $permission;

        // check if the user has any permission that allows him to perform the action
        return $user->hasAnyPermission($permissions, $model, $models);
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user): bool
    {
        return $this->userHasPermission($user, ['viewAny'], [null]);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  User  $user
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Model $model): bool
    {
        return $this->userHasPermission($user, ['view', 'viewAny'], [$model->id, null]);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user): bool
    {
        return $this->userHasPermission($user, ['create'], [null]);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  User  $user
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Model $model): bool
    {
        return $this->userHasPermission($user, ['update', 'updateAny'], [$model->id, null]);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  User  $user
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Model $model): bool
    {
        return $this->userHasPermission($user, ['delete', 'deleteAny'], [$model->id, null]);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  User  $user
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Model $model): bool
    {
        return $this->userHasPermission($user, ['restore', 'restoreAny'], [$model->id, null]);
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  User  $user
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Model $model): bool
    {
        return $this->userHasPermission($user, ['forceDelete', 'forceDeleteAny'], [$model->id, null]);
    }
}