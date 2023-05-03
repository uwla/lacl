<?php

namespace Uwla\Lacl\Traits;

use Exception;
use Uwla\Lacl\Exceptions\NoSuchRoleException;
use Uwla\Lacl\Models\Permission;
use Uwla\Lacl\Models\Role;
use Uwla\Lacl\Models\RolePermission;
use Uwla\Lacl\Models\UserRole;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection;

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
        $roleIds = $this->getBaseQuery()->get()->pluck('role_id');
        return Role::whereIn('id', $roleIds)->get();
    }

    /**
     * Get the name of the roles associated with this model
     *
     * @return array<string>
     */
    public function getRoleNames()
    {
        return $this->getRoles()->pluck('name');
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
        $roles = self::normalizeRoles($roles);

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
        $roles = self::normalizeRoles($roles);
        $ids = $roles->pluck('id');
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
            throw new NoSuchRoleException('Role does not exist');
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
     * add single role to many models
     *
     * @param \Uwla\Lacl\Role|string $role
     * @param \Illuminate\Database\Eloquent\Collection $models
     * @return void
    */
    public static function addRoleToMany($role, $models)
    {
        self::addRolesToMany([$role], $models);
    }

    /**
     * add many roles to many models
     *
     * @param \Uwla\Lacl\Role[]|string[] $roles
     * @param \Illuminate\Database\Eloquent\Collection $models
     * @return void
    */
    public static function addRolesToMany($roles, $models)
    {
        $roles = self::normalizeRoles($roles);

        // will add each role to each model
        $role_ids = $roles->pluck('id');
        $user_ids = $models->pluck('id');
        $toCreate = [];
        foreach ($role_ids as $rid)
        {
            foreach($user_ids as $uid)
            {
                $toCreate[] = [
                    'role_id' => $rid,
                    'user_id' => $uid,
                ];
            }
        }
        UserRole::insert($toCreate);
    }

    /**
     * delete a single role from many models
     *
     * @param \Uwla\Lacl\Role|string $role
     * @param \Illuminate\Database\Eloquent\Collection $models
     * @return void
    */
    public static function delRoleFromMany($role, $models)
    {
        self::delRolesFromMany([$role], $models);
    }

    /**
     * delete many roles from many models
     *
     * @param \Uwla\Lacl\Role[]|string[] $role
     * @param \Illuminate\Database\Eloquent\Collection $models
     * @return void
    */
    public static function delRolesFromMany($roles, $models)
    {
        $roles = self::normalizeRoles($roles);
        $rids = $roles->pluck('id');
        $uids = $models->pluck('id');
        UserRole::whereIn('role_id', $rids)->whereIn('user_id', $uids)->delete();
    }

    /**
     * get how many of the given roles this model has
     *
     * @param Uwla\Lacl\Role[]|string[] $roles
     * @return int
     */
    private function hasHowManyRoles($roles)
    {
        $roles = self::normalizeRoles($roles);
        $ids = $roles->pluck('id');
        $matchedRoles = $this->getBaseQuery()->whereIn('role_id', $ids)->get();
        return $matchedRoles->count();
    }

    /**
     * Normalize $roles into an Eloquent Collection of Role
     *
     * @param mixed $roles
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private static function normalizeRoles($roles)
    {
        if (is_array($roles))
            $roles = collect($roles);
        if (! $roles instanceof Collection)
            throw new Exception('Roles must be collection or array');
        $n = $roles->count();
        if ($n == 0)
            throw new Exception('Roles must not be empty');
        if (is_string($roles->first()))
        {
            $roles = Role::whereIn('name', $roles)->get();
            if ($roles->count() != $n)
                throw new Exception('One or more roles do not exist.');
        }
        if (! $roles->first() instanceof Role)
            throw new Exception('Roles must be valid roles');
        return $roles;
    }
}

?>
