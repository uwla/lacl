<?php

namespace Uwla\Lacl\Traits;

use Illuminate\Database\Eloquent\Collection;
use Uwla\Lacl\Models\Permission;
use Uwla\Lacl\Contracts\HasPermission;

Trait Permissionable
{
    use Identifiable;

    /**
     * Delete all permissions associated with this model.
     *
     * @return void
     */
    public function deletetThisModelPermissions()
    {
        Permission::where([
            'model' => $this::class,
            'model_id' => $this->getModelId(),
        ])->delete();
    }

    /**
     * Format the name of the permission associated with this model.
     *
     * @param string $permissionName
     * @return string
     */
    public static function getPermissionPrefix()
    {
        // @see https://stackoverflow.com/questions/4636166/only-variables-should-be-passed-by-reference
        $tmp = explode('\\', self::class);
        return strtolower(end($tmp));
    }

    /**
     * Create a permission associated with this model given the permission name.
     *
     * @param string $permissionName
     * @return Permission
     */
    protected function createPermission($permissionName): Permission
    {
        return Permission::firstOrCreate([
            'model' => $this::class,
            'model_id' => $this->getModelId(),
            'name' => $this::getPermissionPrefix() . '.' . $permissionName,
        ]);
    }

    /**
     * Get the permission associated with this model given the permission name.
     *
     * @param string $permissionName
     * @return Permission
     */
    protected function getPermission($permissionName): Permission
    {
        return Permission::where([
            'model' => $this::class,
            'model_id' => $this->id,
            'name' => $this::getPermissionPrefix() . '.' . $permissionName,
        ])->first();
    }

    /**
     * Delete the permission associated with this model given the permission name.
     *
     * @param string $permissionName
     * @return void
     */
    protected function deletePermission($permissionName)
    {
        $this->getPermission($permissionName)->delete();
    }

    /**
     * Attach the permission associated with this model given the permission name and the model.
     *
     * @param HasPermission $model
     * @param string $permissionName
     * @return void
     */
    protected function attachPermission(HasPermission $model, $permissionName)
    {
        $permission = $this->getPermission($permissionName);
        $model->addPermission($permission);
    }

    /**
     * Revoke the permission associated with this model given the permission name and the model.
     *
     * @param HasPermission $model
     * @param string $permissionName
     * @return void
     */
    protected function revokePermission(HasPermission $model, $permissionName)
    {
        $permission = $this->getPermission($permissionName);
        $model->delPermission($permission);
    }

    /**
     * Create the view permission associated with this model.
     *
     * @return Permission
     */
    public function createViewPermission(): Permission
    {
        return $this->createPermission('view');
    }

    /**
     * Create the update permission associated with this model.
     *
     * @return Permission
     */
    public function createUpdatePermission(): Permission
    {
        return $this->createPermission('update');
    }

    /**
     * Create the delete permission associated with this model.
     *
     * @return Permission
     */
    public function createDeletePermission(): Permission
    {
        return $this->createPermission('delete');
    }

    /**
     * Create the permission associated with this model.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function createCrudPermissions(): Collection
    {
        // the name of the permissions
        $prefix = $this::getPermissionPrefix();
        $actions = ["$prefix.view", "$prefix.update", "$prefix.delete"];

        // the attributes of the permissions
        $toCreate = [];
        foreach ($actions as $action)
        {
            $toCreate[] = [
                'name' => $action,
                'model' => $this::class,
                'model_id' => $this->id,
            ];
        }

        // insert
        Permission::insert($toCreate);
        return $this->getCrudPermissions();
    }

    /**
     * Get the view permission associated with this model.
     *
     * @return Permission
     */
    public function getViewPermission(): Permission
    {
        return $this->getPermission('view');
    }

    /**
     * Get the update permission associated with this model.
     *
     * @return Permission
     */
    public function getUpdatePermission(): Permission
    {
        return $this->getPermission('update');
    }

    /**
     * Get the delete permission associated with this model.
     *
     * @return Permission
     */
    public function getDeletePermission(): Permission
    {
        return $this->getPermission('delete');
    }

    /**
     * Get the permissions associated with this model.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCrudPermissions(): Collection
    {
        $prefix = $this::getPermissionPrefix();
        $names = ["$prefix.view", "$prefix.update", "$prefix.delete"];
        return Permission::query()
            ->whereIn('name', $names)
            ->where('model', $this::class)
            ->where('model_id', $this->getModelId())
            ->get();
    }

    /**
     * Delete the view permission associated with this model.
     *
     * @return void
     */
    public function deleteViewPermission()
    {
        return $this->deletePermission('view');
    }

    /**
     * Delete the update permission associated with this model.
     *
     * @return void
     */
    public function deleteUpdatePermission()
    {
        return $this->deletePermission('update');
    }

    /**
     * delete the delete permission associated with this model.
     *
     * @return void
     */
    public function deleteDeletePermission()
    {
        return $this->deletePermission('delete');
    }

    /**
     * Delete the permissions associated with this model.
     *
     * @return void
     */
    public function deleteCrudPermissions()
    {
        $prefix = $this::getPermissionPrefix();
        $names = ["$prefix.view", "$prefix.update", "$prefix.delete"];
        Permission::query()
            ->whereIn('name', $names)
            ->where('model', $this::class)
            ->where('model_id', $this->getModelId())
            ->delete();
    }

    /**
     * attach the view permission associated with this model to the given model.
     *
     * @return void
     */
    public function attachViewPermission(HasPermission $model)
    {
        return $this->attachPermission($model, 'view');
    }

    /**
     * attach the update permission associated with this model to the given model.
     *
     * @return void
     */
    public function attachUpdatePermission(HasPermission $model)
    {
        return $this->attachPermission($model, 'update');
    }

    /**
     * attach the delete permission associated with this model to the given model.
     *
     * @return void
     */
    public function attachDeletePermission(HasPermission $model)
    {
        return $this->attachPermission($model, 'delete');
    }

    /**
     * Attach the permissions associated with this model to the given model.
     *
     * @return void
     */
    public function attachCrudPermissions(HasPermission $model)
    {
        $permissions = $this->getCrudPermissions();
        $model->addPermissions($permissions);
    }

    /**
     * revoke the view permission associated with this model to the given model.
     *
     * @return void
     */
    public function revokeViewPermission(HasPermission $model)
    {
        return $this->revokePermission($model, 'view');
    }

    /**
     * revoke the update permission associated with this model to the given model.
     *
     * @return void
     */
    public function revokeUpdatePermission(HasPermission $model)
    {
        return $this->revokePermission($model, 'update');
    }

    /**
     * revoke the delete permission associated with this model to the given model.
     *
     * @return void
     */
    public function revokeDeletePermission(HasPermission $model)
    {
        return $this->revokePermission($model, 'delete');
    }

    /**
     * Revoke the permissions associated with this model to the given model.
     *
     * @return void
     */
    public function revokeCrudPermissions(HasPermission $model)
    {
        $permissions = $this->getCrudPermissions();
        $model->delPermissions($permissions);
    }
}
