<?php

namespace Uwla\Lacl\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Uwla\Lacl\Models\Permission;
use Uwla\Lacl\Contracts\HasPermission;

Trait Permissionable
{
    use Identifiable;

    /**
     * Delete all permissions associated with this model instance.
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
     * Delete all permissions associated with this model class.
     *
     * @return void
     */
    public static function deletetAllModelPermissions()
    {
        Permission::where('model', self::class)->delete();
    }

    /**
     * Delete all permissions associated with this model class.
     *
     * @return void
     */
    public static function deletetGenericModelPermissions()
    {
        Permission::where([
            'model' => self::class,
            'model_id' => null
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
     * Prefix the given strings with this model permission prefix
     *
     * @param  array<string> $permissionName
     * @return array<string>
     */
    protected static function getPrefixed($strings)
    {
        $prefix = self::getPermissionPrefix();
        $result = [];
        foreach ($strings as $string)
            $result[] = $prefix . '.' . $string;
        return $result;
    }

    /**
     * Create a permission associated with this model given the permission name.
     *
     * @param string $permissionName
     * @return Permission
     */
    protected static function createPermission($permissionName, $modelId=null): Permission
    {
        return Permission::firstOrCreate([
            'model' => self::class,
            'model_id' => $modelId,
            'name' => self::getPermissionPrefix() . '.' . $permissionName,
        ]);
    }

    /**
     * Get the permission associated with this model given the permission name.
     *
     * @param string $permissionName
     * @return Permission
     */
    protected static function getPermission($permissionName, $modelId=null): Permission
    {
        return Permission::where([
            'model' => self::class,
            'model_id' => $modelId,
            'name' => self::getPermissionPrefix() . '.' . $permissionName,
        ])->first();
    }

    /**
     * Delete the permission associated with this model given the permission name.
     *
     * @param string $permissionName
     * @return void
     */
    protected static function deletePermission($permissionName, $modelId=null)
    {
        self::getPermission($permissionName, $modelId)->delete();
    }

    /**
     * Attach the permission associated with this model given the permission name and the model.
     *
     * @param HasPermission $model
     * @param string $permissionName
     * @return void
     */
    protected static function attachPermission(HasPermission $model, $permissionName, $modelId=null)
    {
        $permission = self::getPermission($permissionName, $modelId);
        $model->addPermission($permission);
    }

    /**
     * Revoke the permission associated with this model given the permission name and the model.
     *
     * @param HasPermission $model
     * @param string $permissionName
     * @return void
     */
    protected static function revokePermission(HasPermission $model, $permissionName, $modelId=null)
    {
        $permission = self::getPermission($permissionName, $modelId);
        $model->delPermission($permission);
    }

    // BULK OPERATIONS

    /**
     * Create the permission associated with this model.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected static function createManyPermissions($names, $modelId=null): Collection
    {
        $permission_names = self::getPrefixed($names);

        $toCreate = [];
        foreach ($permission_names as $name)
        {
            $toCreate[] = [
                'name' => $name,
                'model' => self::class,
                'model_id' => $modelId,
            ];
        }

        Permission::insert($toCreate);
        return self::getManyPermissions($names, $modelId);
    }

    /**
     * Get the permissions associated with this model.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected static function getManyPermissions($names, $modelId=null): Collection
    {
        $names = self::getPrefixed($names);
        return Permission::query()
            ->whereIn('name', $names)
            ->where('model', self::class)
            ->where('model_id', $modelId)
            ->get();
    }

    /**
     * Delete the permissions associated with this model.
     *
     * @return void
     */
    protected static function deleteManyPermissions($names, $modelId=null)
    {
        $names = self::getPrefixed($names);
        Permission::query()
            ->whereIn('name', $names)
            ->where('model', self::class)
            ->where('model_id', $modelId)
            ->delete();
    }

    /**
     * Attach the permissions associated with this model to the given model.
     *
     * @return void
     */
    protected static function attachManyPermissions(HasPermission $model, $names, $modelId=null)
    {
        $permissions = self::getManyPermissions($names, $modelId);
        $model->addPermissions($permissions);
    }

    /**
     * Revoke the permissions associated with this model to the given model.
     *
     * @return void
     */
    protected static function revokeManyPermissions(HasPermission $model, $names, $modelId=null)
    {
        $permissions = self::getManyPermissions($names, $modelId);
        $model->delPermissions($permissions);
    }

    /**
     * Gets triggered when an unkown method is called upon the this object.
     * We use it to provide syntax sugar for calling some methods.
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $pattern = '/^(get|create|delete|attach|revoke)([A-Za-z]+)Permissions?$/';
        $matches = [];
        if (preg_match($pattern, $name, $matches))
        {
            $operation = $matches[1];                     // get, create, delete, attach, revoke
            $method = $operation . 'Permission';          // getPermission, ..., revokePermission
            $permission_name = Str::lcfirst($matches[2]); // view, update, delete, ..., etc

            if ($permission_name == 'crud')
            {
                $permission_name = ['view', 'update', 'delete'];
                $method = $operation . 'ManyPermissions';
            }

            $arguments[] = $permission_name;    // append to the arg array
            $arguments[] = $this->getModelId(); // append to the arg array
            return call_user_func_array(array(self::class, $method), $arguments);
        }

        return parent::__call($name, $arguments);
    }

     /**
     * Gets triggered when an unkown method is called upon the this object.
     * We use it to provide syntax sugar for calling some methods.
     *
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        $pattern = '/^(get|create|delete|attach|revoke)([a-zA-Z]+)Permissions?$/';
        $matches = [];
        if (preg_match($pattern, $name, $matches))
        {
            $operation = $matches[1];                     // get, create, delete, attach, revoke
            $method = $operation . 'Permission';          // getPermission, ..., revokePermission
            $permission_name = Str::lcfirst($matches[2]); // view, update, delete, ..., etc

            if ($permission_name == 'crud')
            {
                $permission_name = ['create', 'viewAny', 'updateAny', 'deleteAny'];
                $method = $operation . 'ManyPermissions';
            }

            $arguments[] = $permission_name;    // append to the arg array
            return call_user_func_array(array(self::class, $method), $arguments);
        }

        return parent::__callStatic($name, $arguments);
    }

}
