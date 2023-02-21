<?php

namespace Uwla\Lacl\Traits;

use Uwla\Lacl\Exceptions\NoSuchRoleException;
use Uwla\Lacl\Models\Permission;
use Uwla\Lacl\Models\Role;
use Uwla\Lacl\Models\RolePermission;
use Illuminate\Foundation\Auth\User;
use Uwla\Lacl\Models\UserRole;

Trait HasRole
{
    /**
     * Get a base query to keep building on it
     *
     * @return \Illuminate\Database\Eloquent\Builder
    */
    private function getBaseQuery()
    {
        if ($this instanceof User)
            return UserRole::where([['user_id', '=', $this->id], ['role_id', '!=', $this->getSelfRoleId()]]);
        else if ($this instanceof Permission)
            return RolePermission::where('permission_id', $this->id);
    }

    /**
     * Get the roles associated with this model
     *
     * @return \Illuminate\Database\Eloquent\Collection<\Uwla\Lacl\Role>
    */
    public function getRoles()
    {
        $roleIds = $this->getBaseQuery()->get()->pluck('role_id')->toArray();
        return Role::whereIn('id', $roleIds)->get();
    }

    /**
     * Get the name of the roles associated with this model
     *
     * @return array<string>
    */
    public function getRoleNames()
    {
        return $this->getRoles()->pluck('name')->toArray();
    }

    /**
     * add single role
     *
     * @param Uwla\Lacl\Role|string $role
     * @return void
    */
    public function addRole($role)
    {
        $this->addRoles([$role]);
    }

    /**
     * add many roles
     *
     * @param Uwla\Lacl\Role[]|string[] $roles
     * @return void
    */
    public function addRoles($roles)
    {
        if (gettype($roles[0]) == 'string')
        {
            $n = count($roles);
            $roles = Role::whereIn('name', $roles)->get();
            if ($roles->count() != $n)
                throw new NoSuchRoleException();
        }

        if ($this instanceof User) {
            $class = UserRole::class;
            $key = 'user_id';
        } else if ($this instanceof Permission) {
            $class = RolePermission::class;
            $key = 'permission_id';
        }

        $toAdd = [];
        foreach ($roles as $role)
        {
            $toAdd[] = [
                $key => $this->id,
                'role_id' => $role->id,
            ];
        }
        $class::insert($toAdd);
    }

    /**
     * delete single role
     *
     * @param Uwla\Lacl\Role|string $role
     * @return void
    */
    public function delRole($role)
    {
        $this->delRoles([$role]);
    }

    /**
     * delete the given roles
     *
     * @param Uwla\Lacl\Role[]|string[] $roles
     * @return void
    */
    public function delRoles($roles)
    {
        if (count($roles) == 0)
            return;
        if (gettype($roles[0]) == 'string')
            $roles = Role::whereIn('name', $roles)->get();
        $ids = $roles->pluck('id')->toArray();
        $this->getBaseQuery()->whereIn('role_id', $ids)->delete();
    }

    /**
     * delete all roles associated with this model
     *
     * @return void
    */
    public function delAllRoles()
    {
        $this->getBaseQuery()->delete();
    }

    /**
     * set a single role associated with this model
     *
     * @param Uwla\Lacl\Role|string $role
     * @return void
    */
    public function setRole($role)
    {
        $this->setRoles([$role]);
    }

    /**
     * set the role associated with this model
     *
     * @param Uwla\Lacl\Role[]|string[] $roles
     * @return void
    */
    public function setRoles($roles)
    {
        // delete current user roles
        $this->delAllRoles();

        // insert new roles
        $this->addRoles($roles);
    }

    /**
     * count how many roles this model has
     *
     * @return int
    */
    public function countRoles()
    {
        return $this->getBaseQuery()->count();
    }

    /**
     * check whether this model has the given role
     *
     * @param Uwla\Lacl\Role]|string $role
     * @return bool
    */
    public function hasRole($role)
    {
        if (gettype($role) == 'string')
            $role = Role::where('name', $role)->first();

        if (! ($role instanceof Role))
            throw new NoSuchRoleException("Role does not exist");

        return $this->getBaseQuery()->where('role_id', $role->id)->exists();
    }

    /**
     * check whether this model has the given roles
     *
     * @param Uwla\Lacl\Role[]|string[] $role
     * @return bool
    */
    public function hasRoles($roles)
    {
        return $this->hasHowManyRoles($roles) == count($roles);
    }

    /**
     * check whether this model has any of the given roles
     *
     * @param Uwla\Lacl\Role[]|string[] $roles
     * @return bool
    */
    public function hasAnyRoles($roles)
    {
        return $this->hasHowManyRoles($roles) > 0;
    }

    /**
     * get how many of the given roles this model has
     *
     * @param Uwla\Lacl\Role[]|string[] $roles
     * @return int
    */
    private function hasHowManyRoles($roles)
    {
        $n = count($roles);

        if ($n == 0)
            throw new NoSuchRoleException("No role provided");

        if (gettype($roles[0]) == 'string')
            $roles = Role::whereIn('name', $roles)->get();

        if ($roles->count() != $n)
            throw new NoSuchRoleException("One of the roles did not exist");

        if (! ($roles[0] instanceof Role))
            throw new NoSuchRoleException("Roles provided are not roles");

        $roleIds = $roles->pluck('id')->toArray();
        $matchedRoles = $this->getBaseQuery()->whereIn('role_id', $roleIds)->get();

        return $matchedRoles->count();
    }
}

?>
