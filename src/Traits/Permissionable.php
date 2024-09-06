<?php

namespace Uwla\Lacl\Traits;

use Illuminate\Database\Eloquent\DbCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait Permissionable
{
    use Identifiable;
    use CustomAclModels;

    /**
     * Delete all permissions associated with this model instance.
     *
     * @return void
     */
    public function deleteThisModelPermissions(): void
    {
        static::Permission()::where([
            'model' => $this::class,
            'model_id' => $this->getModelId(),
        ])->delete();
    }

    /**
     * Delete all permissions associated with this model class.
     *
     * @return void
     */
    public static function deleteAllModelPermissions(): void
    {
        static::Permission()::where('model', static::class)->delete();
    }

    /**
     * Delete all permissions associated with this model class.
     *
     * @return void
     */
    public static function deleteGenericModelPermissions(): void
    {
        static::Permission()::where([
            'model' => static::class,
            'model_id' => null
        ])->delete();
    }

    /**
     * Get the prefix of the permissions of this model.
     *
     * @return string
     */
    protected static function getPermissionPrefix(): string
    {
        // @see https://stackoverflow.com/questions/4636166/only-variables-should-be-passed-by-reference
        $tmp = explode('\\', static::class);
        return strtolower(end($tmp));
    }

    /**
     * Prefix the given strings with this model permission prefix
     *
     * @param  array<string> $permissionNames
     * @return array<string>
     */
    protected static function getPrefixed($permissionNames): array
    {
        $prefix = static::getPermissionPrefix();
        $result = [];
        foreach ($permissionNames as $name) {
            $result[] = $prefix . '.' . $name;
        }
        return $result;
    }

    /**
     * Create a permission associated with this model given the permission name.
     *
     * @param string        $permissionName
     * @param string|int    $model_id
     * @return \Uwla\Lacl\Models\Permission|Model
     */
    protected static function createPermission($permissionName, $model_id = null): Model
    {
        return static::Permission()::firstOrCreate([
            'model' => static::class,
            'model_id' => $model_id,
            'name' => static::getPermissionPrefix() . '.' . $permissionName,
        ]);
    }

    /**
     * Get the permission associated with this model given the permission name.
     *
     * @param string        $permissionName
     * @param string|int    $model_id
     * @return \Uwla\Lacl\Models\Permission|Model
     */
    protected static function getPermission($permissionName, $model_id = null): Model
    {
        return static::Permission()::where([
            'model' => static::class,
            'model_id' => $model_id,
            'name' => static::getPermissionPrefix() . '.' . $permissionName,
        ])->first();
    }

    /**
     * Delete the permission associated with this model given the permission name.
     *
     * @param string        $permissionName
     * @param string|int    $model_id
     * @return void
     */
    protected static function deletePermission($permissionName, $model_id = null): void
    {
        static::getPermission($permissionName, $model_id)->delete();
    }

    /**
     * Grant the permission to the user or role.
     *
     * @param HasPermission $model
     * @param string        $permissionName
     * @param string|int    $model_id
     * @return void
     */
    protected static function grantPermission($model, $permissionName, $model_id = null): void
    {
        $permission = static::getPermission($permissionName, $model_id);
        $model->addPermission($permission);
    }

    /**
     * Revoke the permission from the user or role.
     *
     * @param HasPermission $model
     * @param string        $permissionName
     * @param string|int    $model_id
     * @return void
     */
    protected static function revokePermission($model, $permissionName, $model_id = null): void
    {
        $permission = static::getPermission($permissionName, $model_id);
        $model->delPermission($permission);
    }

    // BULK OPERATIONS

    /**
     * Create the permission associated with this model.
     *
     * @param array<string> $names
     * @param string|int $model_id
     * @return \Illuminate\Database\Eloquent\DbCollection
     */
    protected static function createManyPermissions($names, $model_id = null): DbCollection
    {
        $permission_names = static::getPrefixed($names);

        $toCreate = [];
        foreach ($permission_names as $name) {
            $toCreate[] = [
                'name' => $name,
                'model' => static::class,
                'model_id' => $model_id,
            ];
        }

        static::Permission()::insert($toCreate);
        return static::getManyPermissions($names, $model_id);
    }

    /**
     * Get the permissions associated with this model.
     *
     * @param array<string> $names
     * @param mixed         $model_id
     * @return \Illuminate\Database\Eloquent\DbCollection
     */
    protected static function getManyPermissions($names, $model_id = null): DbCollection
    {
        $names = static::getPrefixed($names);
        return static::Permission()::query()
            ->whereIn('name', $names)
            ->where('model', static::class)
            ->where('model_id', $model_id)
            ->get();
    }

    /**
     * Delete the permissions associated with this model.
     *
     * @param array<string> $names
     * @param string|int    $model_id
     * @return void
     */
    protected static function deleteManyPermissions($names, $model_id = null): void
    {
        $names = static::getPrefixed($names);
        static::Permission()::query()
            ->whereIn('name', $names)
            ->where('model', static::class)
            ->where('model_id', $model_id)
            ->delete();
    }

    /**
     * Grant many permissions to a user or role.
     *
     * @param HasPermission $model
     * @param array<string> $names
     * @param mixed $model_id
     * @return void
     */
    protected static function grantManyPermissions($model, $names, $model_id = null): void
    {
        $permissions = static::getManyPermissions($names, $model_id);
        $model->addPermissions($permissions);
    }

    /**
     * Revoke the permissions from the user.
     *
     * @param HasPermission $model
     * @param array<string> $names
     * @param string|id $model_id
     * @return void
     */
    protected static function revokeManyPermissions($model, $names, $model_id = null): void
    {
        $permissions = static::getManyPermissions($names, $model_id);
        $model->delPermissions($permissions);
    }

    /**
     * Gets triggered when an unknown method is called upon the this object.
     * We use it to provide syntax sugar for calling some methods.
     *
     * @param string $name      The name of the method
     * @param array  $arguments The arguments passed to the method
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $pattern = '/^(get|create|delete|grant|attach|revoke)([A-Za-z]+)Permissions?$/';
        $matches = [];
        if (preg_match($pattern, $name, $matches)) {
            // Here use Str::replace to ensure backward compatibility with
            // previous interface in order to avoid introducing breaking changes.
            // The `attach` methods were replace by `grant`.
            $operation = Str::replace('attach', 'grant', $matches[1]);
            $method = $operation . 'Permission';
            $permission_name = Str::lcfirst($matches[2]);

            if ($permission_name == 'crud') {
                $permission_name = ['view', 'update', 'delete'];
                $method = $operation . 'ManyPermissions';
            }

            $arguments[] = $permission_name;
            $arguments[] = $this->getModelId();
            return call_user_func_array(array(static::class, $method), $arguments);
        }

        return parent::__call($name, $arguments);
    }

    /**
     * Gets triggered when an unknown method is called upon the this object.
     * We use it to provide syntax sugar for calling some methods.
     *
     * @param string $name      The name of the method
     * @param array  $arguments The arguments passed to the method
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        $pattern = '/^(get|create|delete|grant|attach|revoke)([a-zA-Z]+)Permissions?$/';
        $matches = [];
        if (preg_match($pattern, $name, $matches)) {
            // Here use Str::replace to ensure backward compatibility with
            // previous interface in order to avoid introducing breaking changes.
            // The `attach` methods were replace by `grant`.
            $operation = Str::replace('attach', 'grant', $matches[1]);
            $method = $operation . 'Permission';
            $permission_name = Str::lcfirst($matches[2]);

            if ($permission_name == 'crud') {
                $permission_name = ['create', 'viewAny', 'updateAny', 'deleteAny'];
                $method = $operation . 'ManyPermissions';
            }

            $arguments[] = $permission_name;
            return call_user_func_array(array(static::class, $method), $arguments);
        }

        return parent::__callStatic($name, $arguments);
    }
}