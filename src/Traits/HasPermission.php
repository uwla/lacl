<?php

namespace Uwla\Lacl\Traits;

use BadMethodCallException;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;
use Uwla\Lacl\Contracts\HasRoleContract;
use Uwla\Lacl\Models\Permission;
use Uwla\Lacl\Models\Permissionable;
use Uwla\Lacl\Models\Roleable;

trait HasPermission
{
    use Identifiable;
    use CustomAclModels;

    /**
     * Get the id of this model
     *
     * @return string|int
     */
    protected function getSelfRoleId()
    {
        return $this->id;
    }

    /**
     * Get an eloquent collection of Permission.
     * The parameters permissions & ids can be an array of strings or eloquent models.
     *
     * @param array|Collection  $permissions   The permissions to be normalized
     * @param string            $resource      The class name of the resource model
     * @param array             $ids           The ids of the resources
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private static function normalizePermissions($permissions, $resource = null, $ids = null): Collection
    {
        $normalized = $permissions;
        if (is_array($permissions)) {
            $normalized = collect($permissions);
        }
        if (!$normalized instanceof Collection) {
            throw new Exception('Permissions must be array or Eloquent Collection');
        }
        if (is_string($normalized->first())) {
            $normalized = static::Permission()::getByName($permissions, $resource, $ids);
        } elseif (!$normalized->first() instanceof Permission) {
            throw new Exception('Expected a collection of Permission. Got something else.');
        }
        return $normalized;
    }

    /**
     * Get an eloquent collection of the given models.
     *
     * @param  array|Collection $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private static function normalizeModels($models): Collection
    {
        if (is_array($models)) {
            $models = collect($models);
        }
        if (!$models instanceof Collection) {
            throw new Exception('Models must be array or Eloquent Collection');
        }
        if (is_scalar($models->first())) {
            $models = static::find($models);
        } // find by id
        return $models;
    }

    /**
     * Get the ids of the given permissions
     *
     * @param array|Collection  $permissions   The permissions to be normalized
     * @param string            $resource      The class name of the resource model
     * @param array             $ids           The ids of the resources
     * @return \Illuminate\Support\Collection
     */
    private function getPermissionIds($permissions, $resource = null, $ids = null): Collection
    {
        return $this::normalizePermissions($permissions, $resource, $ids)->pluck('id');
    }

    /**
     * Guess the name of the permission called upon dynamic method.
     *
     * @param  string $remainingMethodName The method name after removing the prefix
     * @return string
     */
    protected function guessPermissionName($remainingMethodName): string
    {
        // by the default, just lower case the first letter of it
        return Str::lcfirst($remainingMethodName);
    }

    /**
     * Add single permission
     *
     * @param string|Permission $permissions    The permission or their names
     * @param string            $resource       The model class
     * @param string|int        $id             The model id
     * @return void
     */
    public function addPermission($permission, $resource = null, $id = null): void
    {
        $this->addPermissions([$permission], $resource, [$id]);
    }

    /**
     * Add many permissions
     *
     * @param array|Collection  $permissions    The permission or their names
     * @param string            $resource       The model class
     * @param string|int        $ids            The model ids
     * @return void
     */
    public function addPermissions($permissions, $resource = null, $ids = null): void
    {
        $permissions = static::normalizePermissions($permissions, $resource, $ids);
        $toAdd = [];
        $model = $this::class;
        $model_id = $this->getSelfRoleId();
        foreach ($permissions as $permission) {
            $toAdd[] = [
                'permissionable_type' => $model,
                'permissionable_id' => $model_id,
                'permission_id' => $permission->id,
            ];
        }
        Permissionable::insert($toAdd);
    }

    /**
     * revoke a permission associated with this role
     *
     * @param string|Permission $permissioni    The permission or its names
     * @param string            $resource       The model class
     * @param string|int        $id             The model id
     * @return void
     */
    public function delPermission($permission, $resource = null, $id = null): void
    {
        $this->delPermissions([$permission], $resource, [$id]);
    }

    /**
     * revoke the given permissions associated with this role
     *
     * @param array|Collection  $permissions    The permission or their names
     * @param string            $resource       The model class
     * @param array             $ids            The model ids
     * @return void
     */
    public function delPermissions($permissions, $resource = null, $ids = null): void
    {
        // get ids of the permissions
        $ids = $this->getPermissionIds($permissions, $resource, $ids);

        // delete current role permissions
        Permissionable::query()
            ->where([
                'permissionable_id' => $this->getSelfRoleId(),
                'permissionable_type' => $this::class,
            ])
            ->whereIn('permission_id', $ids)
            ->delete();
    }

    /**
     * revoke all permissions associated with this role
     *
     * @param mixed $permissions
     * @return void
     */
    public function delAllPermissions()
    {
        Permissionable::where([
            'permissionable_id' => $this->getSelfRoleId(),
            'permissionable_type' => $this::class,
        ])->delete();
    }

    /**
     * set the permissions associated with this role
     *
     * @param mixed $permissions
     * @return void
     */
    public function setPermissions($permissions)
    {
        $this->delAllPermissions();
        $this->addPermissions($permissions);
    }

    /**
     * get the permission ids associated with this object
     *
     * @return \Illuminate\Support\Collection
     */
    private function getThisPermissionsIds()
    {
        $model_id = $this->getSelfRoleId();
        $model = $this::class;

        $query = Permissionable::where([
            'permissionable_type' => $model,
            'permissionable_id' => $model_id
        ]);

        if (! $this instanceof HasRoleContract) {
            return $query->pluck('permission_id');
        }

        $role_ids = Roleable::where([
            'roleable_type' => $model,
            'roleable_id' => $model_id,
        ])->pluck('role_id');

        if ($role_ids->count() > 0) {
            $role_model = $this::Role();
            $query->orWhere(function ($q) use ($role_model, $role_ids) {
                $q->where('permissionable_type', $role_model)
                  ->whereIn('permissionable_id', $role_ids);
            });
        }

        return $query->pluck('permission_id');
    }

    /**
     * get all permissions associated with this object
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPermissions()
    {
        $ids = $this->getThisPermissionsIds();
        return static::Permission()::whereIn('id', $ids)->get();
    }

    /**
     * Get the models this role or user has permission to access.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getModels($class, $permissionNames = [], $addPrefix = true)
    {
        // type validation for the class, using the reflection helper
        $instance = (new ReflectionClass($class))->newInstance();
        if (!$instance instanceof Model) {
            throw new BadMethodCallException('Class should be Eloquent model.');
        }

        if (gettype($permissionNames) == 'string') {
            $permissionNames = [$permissionNames];
        }

        $query = static::Permission()::query()
            ->where('model_type', $class)
            ->whereNotNull('model_id')
            ->whereIn('id', $this->getThisPermissionsIds());

        if (count($permissionNames) > 0) {
            if ($addPrefix) {
                $prefix = $class::getPermissionPrefix();
                $mapper = fn ($name) => "$prefix.$name";
                $permissionNames = Arr::map($permissionNames, $mapper);
            }
            $query = $query->whereIn('name', $permissionNames);
        }
        $permissions = $query->get();

        // get the models by id
        $ids = $permissions->pluck('model_id');
        $models = $class::whereIn('id', $ids)->get();
        return $models;
    }

    /**
     * Get the permissions associated with this model only, not with its roles.
     *
     * @return \Illuminate\Database\Eloquent\Collection<\Uwla\Lacl\Models\Permission>
     */
    public function getSelfPermissions()
    {
        $ids = Permissionable::where([
            'permissionable_type' => $this::class,
            'permissionable_id' => $this->getSelfRoleId(),
        ])->pluck('permission_id');
        return static::Permission()::whereIn('id', $ids)->get();
    }

    /**
     * get the name of the permissions associated with this object
     *
     * @return \Illuminate\Support\Collection
     */
    public function getPermissionNames()
    {
        return $this->getPermissions()->pluck('name');
    }

    /**
     * check whether this object has the given permission
     *
     * @param mixed  $permission
     * @param string $resource
     * @param mixed  $id
     * @return bool
     */
    public function hasPermission($permission, $resource = null, $id = null)
    {
        return $this->hasPermissions([$permission], $resource, [$id]);
    }

    /**
     * Executed when the object is called upon an undefined method.
     * We overwrote it to provide better interface to manage permissions.
     *
     * If the method called starts with 'hasPermissionTo', our custom handler
     * will be called, otherwise we delegated it to the default handler.
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $pattern = '/^(add|has|del)PermissionTo([A-Za-z]+)$/';
        $matches = [];
        if (preg_match($pattern, $name, $matches)) {
            $operation = $matches[1];
            $method = $operation . 'Permission';
            $permission_name = Str::lcfirst($matches[2]);
            $args = [];
            if (empty($arguments)) {
                $args = [$permission_name];
            } else {
                $model = $arguments[0];
                $class = $model::class;
                $id = $model->getModelId();
                $permission_prefix = $model::getPermissionPrefix();
                $permission_name = $permission_prefix . '.' . $permission_name;
                $args = [$permission_name, $class, $id];
            }
            return call_user_func_array(array($this, $method), $args);
        }
        return parent::__call($name, $arguments);
    }

    /**
     * check whether this object has the given permissions
     *
     * @param mixed  $permission
     * @param string $resource
     * @param mixed  $ids
     * @return bool
     */
    public function hasPermissions($permissions, $resource = null, $ids = null)
    {
        $n = count($permissions);
        $m = $this->hasHowManyPermissions($permissions, $resource, $ids);
        return $m == $n;
    }

    /**
     * check whether this object has any of the given permissions
     *
     * @param mixed  $permission
     * @param string $resource
     * @param array  $ids
     * @return bool
     */
    public function hasAnyPermission($permissions, $resource = null, $ids = null): bool
    {
        $m = $this->hasHowManyPermissions($permissions, $resource, $ids);
        return $m > 0;
    }

    /**
     * get how many of the given permissions this object has
     *
     * @param array|Collection  $permissions
     * @param string            $resource
     * @param array             $ids
     * @return int
     */
    private function hasHowManyPermissions($permissions, $resource, $ids): int
    {
        // the ids of the given permissions
        $permission_ids = $this->getPermissionIds($permissions, $resource, $ids)->toArray();

        // the ids of the permissions of this model
        $this_permission_ids = $this->getThisPermissionsIds()->toArray();

        return count(array_intersect($permission_ids, $this_permission_ids));
    }

    /**
     * get how many permissions this object has
     *
     * @return int
     */
    public function countPermissions(): int
    {
        return $this->getThisPermissionsIds()->count();
    }

    /**
     * Add single permission to many models
     *
     * @param string|Permission $permission
     * @param \Illuminate\Database\Eloquent\Collection $models
     * @return void
     */
    public static function addPermissionToMany($permission, $models): void
    {
        static::addPermissionsToMany([$permission], $models);
    }

    /**
     * Add many permissions to many models
     *
     * @param array|Collection $permissions
     * @param \Illuminate\Database\Eloquent\Collection $models
     * @return void
     */
    public static function addPermissionsToMany($permissions, $models): void
    {
        $permissions = static::normalizePermissions($permissions);
        $models = static::normalizeModels($models);
        $toCreate = [];
        foreach ($permissions as $permission) {
            // If the models are users, one query per user is made.
            // If the models are roles, no query is made.
            //
            // Devs are discouraged  from  adding  permissions  directly  to
            // users since it may be very costly and make little  sense.  If
            // permissions shall be granted to many  users,  then  the  best
            // thing to do would be to create a role, grant the  permissions
            // to that role, then assign that role to the users. This  shall
            // be explained in the documentation.
            foreach ($models as $model) {
                $toCreate[] = [
                    'permission_id' => $permission->id,
                    'permissionable_id' => $model->getSelfRoleId(),
                    'permissionable_type' => $model::class,
                ];
            }
        }
        Permissionable::insert($toCreate);
    }

    /**
     * Delete a single permission from many models
     *
     * @param string|Permission $permission
     * @param \Illuminate\Database\Eloquent\Collection $models
     * @return void
     */
    public static function delPermissionFromMany($permission, $models): void
    {
        static::delPermissionsFromMany([$permission], $models);
    }

    /**
     * Delete many permissions from many models
     *
     * @param array|Collection $permissions
     * @param array|Collection $models
     * @return void
     */
    public static function delPermissionsFromMany($permissions, $models): void
    {
        $permissions = static::normalizePermissions($permissions);
        $models = static::normalizeModels($models);
        $permission_ids = $permissions->pluck('id');
        $role_ids = $models->map(fn ($m) => $m->getSelfRoleId());
        $model = $models->first()::class;
        Permissionable::query()
            ->whereIn('permission_id', $permission_ids)
            ->whereIn('permissionable_id', $role_ids)
            ->where('permissionable_type', $model)
            ->delete();
    }

    /**
     * Get the name of the id column of this model class
     *
     * @return string
     */
    public static function getIdColumn()
    {
        return 'id';
    }

    /**
     * Get the given roles with their permissions
     *
     * @param  array|Collection $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function withPermissions($models): Collection
    {
        $roles = static::normalizeModels($models);
        $model_ids = $roles->pluck('id');
        $model = $roles->first()::class;
        $role_permissions = Permissionable::query()
            ->where('permissionable_type', $model)
            ->whereIn('permissionable_id', $model_ids)
            ->get();
        $permission_ids = $role_permissions->pluck('permission_id');
        $permissions = Permission::whereIn('id', $permission_ids)->get();

        // build a map ID -> role
        $id2role = [];
        foreach ($roles as $role) {
            $role_id = $role->id;
            $id2role[$role_id] = $role;
        }

        // build a map ID -> PERMISSION
        $id2permission = [];
        foreach ($permissions as $permission) {
            $permission_id = $permission->id;
            $id2permission[$permission_id] = $permission;
        }

        // initialize permissions array
        foreach ($roles as $role) {
            $role->permissions = collect();
        }

        // assign the permission to the model
        foreach ($role_permissions as $role_permission) {
            $role_id = $role_permission->permissionable_id;
            $permission_id = $role_permission->permission_id;
            $role = $id2role[$role_id];
            $permission = $id2permission[$permission_id];
            $role->permissions->add($permission);
        }

        // return the roles
        return $roles;
    }

    /**
     * Get the given roles with their permission names
     *
     * @param  array|Collection $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function withPermissionNames($models): Collection
    {
        $models = static::withPermissions($models);
        foreach ($models as $model) {
            $model->permissions = $model->permissions->pluck('name');
        }
        return $models;
    }
}
