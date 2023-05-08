<?php

namespace Uwla\Lacl\Traits;

use BadMethodCallException;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;
use Uwla\Lacl\Models\Permission;
use Uwla\Lacl\Models\Role;
use Uwla\Lacl\Models\RolePermission;
use Uwla\Lacl\Models\UserRole;

Trait HasPermission
{
    use Identifiable, CustomAclModels;

    /**
     * Ensure this object is instance of a Role.
     * Some methods should only be called from a Role.
     *
     * @return void
     */
    private function getSelfRoleId()
    {
        if ($this instanceof Role)
            return $this->id;

        if ($this instanceof User)
        {
            $roleName = $this::class . ':' . $this->id;

            // get the role that uniquely represents this user
            $role = self::Role()::where('name', $roleName)->first();

            // if null, create it
            if ($role == null)
            {
                $role = self::Role()::create(['name' => $roleName]);
                $this->addRole($role);
            }

            // return it
            return $role->id;
        }

        throw new BadMethodCallException(
            'HasPermission shall be used only with Role or User class'
        );
    }

    /**
     * Get an eloquent collection of Permission.
     * The parameters permissions & ids can be an array of strings or eloquent models.
     *
     * @param mixed  $permissions   The permissions to be normalized
     * @param string $resource      The class name of the resource model
     * @param mixed  $ids           The ids of the resources
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private static function normalizePermissions($permissions, $resource=null, $ids=null)
    {
        $normalized = $permissions;
        if (is_array($permissions))
            $normalized = collect($permissions);
        if (! $normalized instanceof Collection)
            throw new Exception('Permissions must be array or Eloquent Collection');
        if (is_string($normalized->first()))
            $normalized = self::Permission()::getByName($permissions, $resource, $ids);
        else if (! $normalized->first() instanceof Permission)
            throw new Exception('Expected a collection of Permission. Got something else.');
        return $normalized;
    }

    /**
     * Get an eloquent collection of the given models.
     *
     * @param mixed $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private static function normalizeModels($models)
    {
        if (is_array($models))
            $models = collect($models);
        if (! $models instanceof Collection)
            throw new Exception('Models must be array or Eloquent Collection');
        if (is_scalar($models->first()))
            $models = self::find($models); // find by id
        return $models;
    }

    /**
     * Get the ids of the given permissions
     *
     * @param mixed  $permissions   The permissions to be normalized
     * @param string $resource      The class name of the resource model
     * @param mixed  $ids           The ids of the resources
     * @return array<int>
     */
    private function getPermissionIds($permissions, $resource=null, $ids=null)
    {
        return $this::normalizePermissions($permissions, $resource, $ids)->pluck('id');
    }

    /**
     * Get the ids of the object's roles
     *
     * @return array<int>
     */
    private function getRoleIds()
    {
        $id = $this->id;
        if ($this instanceof User)
            return UserRole::where('user_id', $id)->get()->pluck('role_id');
        else if ($this instanceof Role)
            return [$id];
    }


    /**
     * Guess the name of the permission called upon dynamic method.
     *
     * @param  string $remainingMethodName The method name after removing the prefix
     * @return string
     */
    protected function guessPermissionName($remainingMethodName)
    {
        // by the default, just lower case the first letter of it
        return Str::lcfirst($remainingMethodName);
    }

    /**
     * add single permission
     *
     * @param mixed $permissions    The permission or their names
     * @param mixed $resource       The model class
     * @param mixed $id             The model id
     * @return void
     */
    public function addPermission($permission, $resource=null, $id=null)
    {
        $this->addPermissions([$permission], $resource, [$id]);
    }

    /**
     * add many permissions
     *
     * @param mixed $permissions    The permission or their names
     * @param mixed $resource       The model class
     * @param mixed $ids            The model ids
     * @return void
     */
    public function addPermissions($permissions, $resource=null, $ids=null)
    {
        $permissions = self::normalizePermissions($permissions, $resource, $ids);
        $toAdd = [];
        foreach ($permissions as $permission)
        {
            $toAdd[] = [
                'permission_id' => $permission->id,
                'role_id' => $this->getSelfRoleId()
            ];
        }
        RolePermission::insert($toAdd);
    }

    /**
     * revoke a permission associated with this role
     *
     * @param mixed $permissions    The permission or their names
     * @param mixed $resource       The model class
     * @param mixed $id             The model id
     * @return void
     */
    public function delPermission($permission, $resource=null, $id=null)
    {
        $this->delPermissions([$permission], $resource, [$id]);
    }

    /**
     * revoke the given permissions associated with this role
     *
     * @param mixed $permissions    The permission or their names
     * @param mixed $resource       The model class
     * @param mixed $ids            The model ids
     * @return void
     */
    public function delPermissions($permissions, $resource=null, $ids=null)
    {
        // get ids of the permissions
        $ids = $this->getPermissionIds($permissions, $resource, $ids);

        // delete current role permissions
        RolePermission::query()
            ->where('role_id', $this->getSelfRoleId())
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
        RolePermission::where('role_id', $this->getSelfRoleId())->delete();
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
     * @return \Illuminate\Database\Eloquent\Collection<\Uwla\Lacl\Models\Permission>
     */
    private function getThisPermissionsIds()
    {
        $roleIds = $this->getRoleIds();

        // if should have at least one role; otherwise has no permission
        if (count($roleIds) == 0)
            return [];

        // get the ids of the permissions associated with this object's roles
        return RolePermission::query()
            ->whereIn('role_id', $roleIds)
            ->get()
            ->pluck('permission_id');
    }

    /**
     * get all permissions associated with this object
     *
     * @return \Illuminate\Database\Eloquent\Collection<\Uwla\Lacl\Models\Permission>
      */
    public function getPermissions()
    {
        $ids = $this->getThisPermissionsIds();
        return self::Permission()::whereIn('id', $ids)->get();
    }

    /**
     * Get the models this role or user has permission to access.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getModels($class, $permissionNames=[], $addPrefix=true)
    {
        // type validation for the class, using the reflection helper
        $instance = (new ReflectionClass($class))->newInstance();
        if (! $instance instanceof Model)
            throw new BadMethodCallException('Class should Eloquent model.');

        if (gettype($permissionNames) == 'string')
            $permissionNames = [$permissionNames];

        $query = self::Permission()::query()
            ->where('model', $class)
            ->whereNotNull('model_id')
            ->whereIn('id', $this->getThisPermissionsIds());

        if (count($permissionNames) > 0)
        {
            if ($addPrefix)
            {
                $prefix = $class::getPermissionPrefix();
                $mapper = fn($name) => "$prefix.$name";
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
     * get the permissions associated with this user only, not with its roles.
     *
     * @return \Illuminate\Database\Eloquent\Collection<\Uwla\Lacl\Models\Permission>
     */
    public function getUserPermissions()
    {
        if (! $this instanceof User)
            throw new BadMethodCallException("Should only be called upon a User.");

        $ids = RolePermission::query()
            ->whereIn('role_id', $this->getSelfRoleId())
            ->get()
            ->pluck('permission_id');

        // retrieve the permissions by id
        return self::Permission()::whereIn('id', $ids)->get();
    }

    /**
     * get the name of the permissions associated with this object
     *
     * @return array<string>
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
    public function hasPermission($permission, $resource=null, $id=null)
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
        if (preg_match($pattern, $name, $matches))
        {
            $operation = $matches[1];
            $method = $operation . 'Permission';
            $permission_name = Str::lcfirst($matches[2]);
            $args = [];
            if (empty($arguments))
            {
                $args = [ $permission_name ];
            } else {
                $model = $arguments[0];
                $class = $model::class;
                $id = $model->getModelId();
                $permission_prefix = $model::getPermissionPrefix();
                $permission_name = $permission_prefix . '.' . $permission_name;
                $args = [ $permission_name, $class, $id ];
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
    public function hasPermissions($permissions, $resource=null, $ids=null)
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
     * @param mixed  $ids
     * @return bool
     */
    public function hasAnyPermission($permissions, $resource=null, $ids=null)
    {
        $m = $this->hasHowManyPermissions($permissions, $resource, $ids);
        return $m > 0;
    }

    /**
     * get how many of the given permissions this object has
     *
     * @param mixed  $permission
     * @param string $resource
     * @param mixed  $ids
     * @return int
     */
    private function hasHowManyPermissions($permissions, $resource, $ids)
    {
        $roleIds = $this->getRoleIds();
        if (count($roleIds) == 0) return 0;

        $permissionIds = $this->getPermissionIds($permissions, $resource, $ids);

        return RolePermission::query()
            ->whereIn('role_id', $roleIds)
            ->whereIn('permission_id', $permissionIds)
            ->count();
    }

    /**
     * get how many permissions this object has
     *
     * @return int
     */
    public function countPermissions()
    {
        $roleIds = $this->getRoleIds();
        if (count($roleIds) == 0) return 0;
        return RolePermission::whereIn('role_id', $roleIds)->count();
    }

    /**
     * add single permission to many models
     *
     * @param \Uwla\Lacl\Permission|string $permission
     * @param \Illuminate\Database\Eloquent\Collection $models
     * @return void
    */
    public static function addPermissionToMany($permission, $models)
    {
        self::addPermissionsToMany([$permission], $models);
    }

    /**
     * add many permissions to many models
     *
     * @param \Uwla\Lacl\Permission[]|string[] $permission
     * @param \Illuminate\Database\Eloquent\Collection $models
     * @return void
    */
    public static function addPermissionsToMany($permissions, $models)
    {
        $permissions = self::normalizePermissions($permissions);
        $models = self::normalizeModels($models);
        $toCreate = [];
        foreach ($permissions as $permission)
        {
            foreach ($models as $model)
            {
                // If the models are roles, no query is made.
                // If the models are users, one query per user is made.
                //
                // Devs are discouraged  from  adding  permissions  directly  to
                // users since it may be very costly and make little  sense.  If
                // permissions shall be granted to many  users,  then  the  best
                // thing to do would be to create a role, grant the  permissions
                // to that role, then assign that role to the users. This  shall
                // be explained in the documentation.
                $toCreate[] = [
                    'permission_id' => $permission->id,
                    'role_id' => $model->getSelfRoleId(),
                ];
            }
        }
        RolePermission::insert($toCreate);
    }

   /**
     * delete a single permission from many models
     *
     * @param \Uwla\Lacl\Permission|string $permission
     * @param \Illuminate\Database\Eloquent\Collection $models
     * @return void
    */
    public static function delPermissionFromMany($permission, $models)
    {
        self::delPermissionsFromMany([$permission], $models);
    }

    /**
     * delete many permissions from many models
     *
     * @param \Uwla\Lacl\Permission[]|string[] $permission
     * @param \Illuminate\Database\Eloquent\Collection $models
     * @return void
    */
    public static function delPermissionsFromMany($permissions, $models)
    {
        $permissions = self::normalizePermissions($permissions);
        $models = self::normalizeModels($models);
        $pids = $permissions->pluck('id');
        $rids = $models->map(fn($m) => $m->getSelfRoleId());
        RolePermission::query()
            ->whereIn('permission_id', $pids)
            ->whereIn('role_id', $rids)
            ->delete();
    }
}

?>
