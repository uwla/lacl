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
use Uwla\Lacl\Models\PermissionModel;
use Uwla\Lacl\Models\RoleModel;

Trait HasPermission
{
    use Identifiable, CustomAclModels;

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
        if (is_array($permissions))
            $normalized = collect($permissions);
        if (!$normalized instanceof Collection)
            throw new Exception('Permissions must be array or Eloquent Collection');
        if (is_string($normalized->first()))
            $normalized = static::Permission()::getByName($permissions, $resource, $ids);
        else if (!$normalized->first() instanceof Permission)
            throw new Exception('Expected a collection of Permission. Got something else.');
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
        if (is_array($models))
            $models = collect($models);
        if (!$models instanceof Collection)
            throw new Exception('Models must be array or Eloquent Collection');
        if (is_scalar($models->first()))
            $models = static::find($models); // find by id
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
                'model_type' => $model,
                'model_id' => $model_id,
                'permission_id' => $permission->id,
            ];
        }
        PermissionModel::insert($toAdd);
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
        PermissionModel::query()
            ->where([
                'model_id' => $this->getSelfRoleId(),
                'model_type' => $this::class,
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
        PermissionModel::where([
            'model_id' => $this->getSelfRoleId(),
            'model_type' => $this::class,
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

        $query = PermissionModel::where([
            'model_type' => $model,
            'model_id' => $model_id
        ]);

        if ($this instanceof HasRoleContract) {
            $role_ids = RoleModel::where([
                'model_type' => $model,
                'model_id' => $model_id
            ])->pluck('role_id');

            if ($role_ids->count() > 0) {
                $role_model = $this::Role();
                $query->orWhere(function ($q) use ($role_model, $role_ids) {
                    $q->where('model_type', $role_model)->whereIn('model_id', $role_ids);
                });
            }
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
        if (!$instance instanceof Model)
            throw new BadMethodCallException('Class should be Eloquent model.');

        if (gettype($permissionNames) == 'string')
            $permissionNames = [$permissionNames];

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
        $ids = PermissionModel::where([
            'model_type' => $this::class,
            'model_id' => $this->getSelfRoleId(),
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
        // the ids of the permissions
        $permission_ids = $this->getPermissionIds($permissions, $resource, $ids)->toArray();

        // the ids of the permissions of this model
        $this_permission_ids = $this->getThisPermissionsIds()->toArray();

        // count the intersections
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
                    'model_id' => $model->getSelfRoleId(),
                    'model_type' => $model::class,
                ];
            }
        }
        PermissionModel::insert($toCreate);
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
        $pids = $permissions->pluck('id');
        $rids = $models->map(fn ($m) => $m->getSelfRoleId());
        $model = $models->first()::class;
        PermissionModel::query()
            ->whereIn('permission_id', $pids)
            ->whereIn('model_id', $rids)
            ->where('model_type', $model)
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
        // normalize models
        $roles = static::normalizeModels($models);

        // get the roles ids
        $mids = $roles->pluck('id');

        // the model class
        $model = $roles->first()::class;

        // get the association models
        $rps = PermissionModel::query()
            ->where('model_type', $model)
            ->whereIn('model_id', $mids)
            ->get();

        // get the permission ids
        $pids = $rps->pluck('permission_id');

        // get the permissions
        $permissions = Permission::whereIn('id', $pids)->get();

        // build a map ID -> role
        $id2role = [];
        foreach ($roles as $r) {
            $rid = $r->id;
            $id2role[$rid] = $r;
        }

        // build a map ID -> PERMISSION
        $id2permission = [];
        foreach ($permissions as $p) {
            $pid = $p->id;
            $id2permission[$pid] = $p;
        }

        // initialize permissions array
        foreach ($roles as $r)
            $r->permissions = collect();

        // assign the permission to the model
        foreach ($rps as $rp) {
            $rid = $rp->model_id;
            $pid = $rp->permission_id;
            $r = $id2role[$rid];
            $p = $id2permission[$pid];
            $r->permissions->add($p);
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
        foreach ($models as $m)
            $m->permissions = $m->permissions->pluck('name');
        return $models;
    }
}