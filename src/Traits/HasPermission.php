<?php

namespace Uwla\Lacl\Traits;

use BadMethodCallException;
use Illuminate\Database\Eloquent\Collection;
use Uwla\Lacl\Models\Permission;
use Uwla\Lacl\Models\Role;
use Uwla\Lacl\Models\RolePermission;
use Illuminate\Foundation\Auth\User;
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
            "HasPermission shall be used only with Role or User class"
        );
    }

    /**
     * Get an eloquent collection of Permission.
     * The parameters permissions & ids can be an array of strings or eloquent models.
     *
     * @param mixed  $permissions   The permissions to be normalized
     * @param string $resource      The class name of the resource model
     * @param mixed  $ids           The ids of the resources
     * @return \Illuminate\Database\Eloquent\Collection<\Uwla\Lacl\Permission>
     */
    private function normalizePermissions($permissions, $resource=null, $ids=null)
    {
        if (gettype($permissions[0]) == 'string')
        {
            $permissions = Permission::getPermissionsByName($permissions, $resource, $ids);
        } else if (gettype($permissions) == 'array') {
            $permissions = new Collection($permissions);
        }
        return $permissions;
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
            ->pluck('id')
            ->toArray();
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
                ->pluck('role_id')
                ->toArray();
        } else if ($this instanceof Role)
            return [$this->id];
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
     * get the permissions associated with this object
     *
     * @return \Illuminate\Database\Eloquent\Collection<\Uwla\Lacl\Models\Permission>
      */
    public function getPermissions()
    {
        $roleIds = $this->getRoleIds();

        // if should have at least one role; otherwise has no permission
        if (count($roleIds) == 0)
            return [];

        // get the ids of the permissions associated with this object's roles
        $ids = RolePermission::query()
            ->whereIn('role_id', $roleIds)
            ->get()
            ->pluck('permission_id')
            ->toArray();

        // retrieve the permissions by id
        return Permission::whereIn('id', $ids)->get();
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
            ->pluck('permission_id')
            ->toArray();

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
        return $this->getPermissions()->pluck('name')->toArray();
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
