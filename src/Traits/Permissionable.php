<?php

namespace Uwla\Lacl\Traits;

use Uwla\Lacl\Models\Permission;
use Uwla\Lacl\Traits\HasPermission;

Trait Permissionable
{
    /**
     * Register callback to delete permissions associated with this model when it gets deleted.
     *
     * @return void
     */
    protected static function boot() {
        parent::boot();

        static::deleted(function($model) {
            Permission::where([
                'model' => $model::class,
                'model_id' => $model->id,
            ])->delete();
        });
    }

    /**
     * Format the name of the permission associated with this model.
     *
     * @param string $permissionName
     * @return void
     */
    protected function formatPermissionName($permissionName)
    {
        $prefix = strtolower(end(explode('\\', $this::class)));
        return $prefix . '.' . strtolower($permissionName);
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
            'model_id' => $this->id,
            'name' => $this->formatPermissionName($permissionName),
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
            'name' => $this->formatPermissionName($permissionName),
        ])->first();
    }

    /**
     * Delete the permission associated with this model given the permission name.
     *
     * @param string $permissionName
     * @return Permission
     */
    protected function deletePermission($permissionName)
    {
        Permission::where([
            'model' => $this::class,
            'model_id' => $this->id,
            'name' => $this->formatPermissionName($permissionName),
        ])->delete();
    }

    /**
     * Attach the permission associated with this model given the permission name and the model.
     *
     * @param HasPermission $model
     * @param string $permissionName
     * @return Permission
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
     * @return Permission
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
     * @return void
     */
    public function getDeletePermission(): Permission
    {
        return $this->getPermission('delete');
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
    }}
