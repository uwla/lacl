<?php

namespace Uwla\Lacl\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Uwla\Lacl\Models\Permission;
use Uwla\Lacl\Contracts\HasPermission;

Interface Permissionable
{
    /**
     * Get the id of the model
     *
     * @return mixed
     */
    public function getModelId();

    /**
     * Format the name of the permission associated with this model.
     *
     * @param string $permissionName
     * @return void
     */
    public function getPermissionPrefix();

    /**
     * Create the view permission associated with this model.
     *
     * @return Permission
     */
    public function createViewPermission(): Permission;

    /**
     * Create the update permission associated with this model.
     *
     * @return Permission
     */
    public function createUpdatePermission(): Permission;

    /**
     * Create the delete permission associated with this model.
     *
     * @return Permission
     */
    public function createDeletePermission(): Permission;

    /**
     * Create the permission associated with this model.
     *
     * @return Permission
     */
    public function createCrudPermissions(): Collection;

    /**
     * Get the view permission associated with this model.
     *
     * @return Permission
     */
    public function getViewPermission(): Permission;

    /**
     * Get the update permission associated with this model.
     *
     * @return Permission
     */
    public function getUpdatePermission(): Permission;

    /**
     * Get the delete permission associated with this model.
     *
     * @return void
     */
    public function getDeletePermission(): Permission;

    /**
     * Get the permissions associated with this model.
     *
     * @return Permission
     */
    public function getCrudPermissions(): Collection;

    /**
     * Delete the view permission associated with this model.
     *
     * @return void
     */
    public function deleteViewPermission();

    /**
     * Delete the update permission associated with this model.
     *
     * @return void
     */
    public function deleteUpdatePermission();

    /**
     * delete the delete permission associated with this model.
     *
     * @return void
     */
    public function deleteDeletePermission();

    /**
     * attach the view permission associated with this model to the given model.
     *
     * @return void
     */
    public function attachViewPermission(HasPermission $model);

    /**
     * attach the update permission associated with this model to the given model.
     *
     * @return void
     */
    public function attachUpdatePermission(HasPermission $model);

    /**
     * attach the delete permission associated with this model to the given model.
     *
     * @return void
     */
    public function attachDeletePermission(HasPermission $model);

    /**
     * Attach the permissions associated with this model to the given model.
     *
     * @return void
     */
    public function attachCrudPermissions(HasPermission $model);

    /**
     * revoke the view permission associated with this model to the given model.
     *
     * @return void
     */
    public function revokeViewPermission(HasPermission $model);

    /**
     * revoke the update permission associated with this model to the given model.
     *
     * @return void
     */
    public function revokeUpdatePermission(HasPermission $model);

    /**
     * revoke the delete permission associated with this model to the given model.
     *
     * @return void
     */
    public function revokeDeletePermission(HasPermission $model);

    /**
     * Revoke the permissions associated with this model to the given model.
     *
     * @return void
     */
    public function revokeCrudPermissions(HasPermission $model);
}
