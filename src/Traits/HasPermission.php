<?php

namespace Uwla\Lacl\Traits;

use ArgumentCountError;
use BadMethodCallException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionClass;
use Uwla\Lacl\Contracts\Permissionable;
use Uwla\Lacl\Models\Permission;
use Uwla\Lacl\Models\Role;
use Uwla\Lacl\Models\RolePermission;
use Uwla\Lacl\Models\UserRole;

Trait HasPermission
{
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
            $role = Role::where('name', $roleName)->first();

            // if null, create it
            if ($role == null)
            {
                $role = Role::create(['name' => $roleName]);
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
    private function normalizePermissions($permissions, $resource=null, $ids=null)
    {
        $normalized = $permissions;
        if (is_array($permissions))
            $normalized = collect($permissions);
        if (is_string($normalized->first()))
            $normalized = Permission::getPermissionsByName($permissions, $resource, $ids);
        return $normalized;
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
        return $this
            ->normalizePermissions($permissions, $resource, $ids)
            ->pluck('id');
    }

    /**
     * Get the ids of the object's roles
     *
     * @return array<int>
     */
    private function getRoleIds()
    {
        if ($this instanceof User) {
            return UserRole::query()
                ->where('user_id', $this->id)
                ->get()
                ->pluck('role_id');
        } else if ($this instanceof Role)
            return [$this->id];
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
     * @param mixed $permission
     * @return void
     */
    public function addPermission($permission)
    {
        $this->addPermissions([$permission]);
    }

    /**
     * add many permissions
     *
     * @param mixed $permissions
     * @return void
     */
    public function addPermissions($permissions)
    {
        $permissions = $this->normalizePermissions($permissions);
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
     * @param mixed $permission
     * @return void
     */
    public function delPermission($permission)
    {
        $this->delPermissions([$permission]);
    }

    /**
     * revoke the given permissions associated with this role
     *
     * @param mixed $permissions
     * @return void
     */
    public function delPermissions($permissions)
    {
        // get ids of the permissions
        $ids = $this->getPermissionIds($permissions);

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
        return Permission::whereIn('id', $ids)->get();
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
        if (! $instance instanceof Permissionable)
            throw new BadMethodCallException('Class should abide to Permissionable contract.');
        if (! $instance instanceof Model)
            throw new BadMethodCallException('Class should Eloquent model.');

        if (gettype($permissionNames) == 'string')
            $permissionNames = [$permissionNames];

        $query = Permission::query()
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
        return Permission::whereIn('id', $ids)->get();
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
        $possiblePrefixes = ['hasPermissionTo', 'addPermissionTo', 'delPermissionTo'];
        $matchingPrefix = '';

        // if the undefined method called does not start with the prefix, let
        // the parent class deal with it.
        foreach ($possiblePrefixes as $prefix)
        {
            if (Str::startsWith($name, $prefix))
            {
                $matchingPrefix = $prefix;
                break;
            }
        }

        if ($matchingPrefix == '')
            return parent::__call($name, $arguments);

        // get the name of the permission called after it
        $suffix = Str::after($name, $matchingPrefix);
        $permissionName = $this->guessPermissionName($suffix);

        if (empty($arguments))
        {
            if ($matchingPrefix == 'hasPermissionTo')
                return $this->hasPermission($permissionName);
            if ($matchingPrefix == 'addPermissionTo')
                return $this->addPermission($permissionName);
            if ($matchingPrefix == 'delPermissionTo')
                return $this->delPermission($permissionName);
        }

        // do some argument validation
        if (count($arguments) != 1)
            throw new ArgumentCountError("Too much arguments");
        $model = $arguments[0];
        if (!$model instanceof Permissionable)
            throw new BadMethodCallException("Argument must be of type Permissionable");

        // now, extract the adequate values
        $permissionPrefix = $model::getPermissionPrefix();
        $permissionName = $permissionPrefix . '.' . $permissionName;
        $id = $model->getModelId();
        $class = $model::class;

        if ($matchingPrefix == 'hasPermissionTo')
            return $this->hasPermission($permissionName, $class, $id);
        if ($matchingPrefix == 'addPermissionTo')
            return $this->addPermission($permissionName, $class, $id);
        if ($matchingPrefix == 'delPermissionTo')
            return $this->delPermission($permissionName, $class, $id);
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
}

?>
