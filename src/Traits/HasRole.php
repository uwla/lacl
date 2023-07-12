<?php

namespace Uwla\Lacl\Traits;

use Exception;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection;
use Uwla\Lacl\Models\Permission;
use Uwla\Lacl\Models\Role;
use Uwla\Lacl\Models\RolePermission;
use Uwla\Lacl\Models\UserRole;

Trait HasRole
{
    use HasPermission;

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
        else
            throw new Exception('This class should be instance of User or Permission.');
    }

    /**
     * Get the roles associated with this model
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRoles()
    {
        $roleIds = $this->getBaseQuery()->get()->pluck('role_id');
        return self::Role()::whereIn('id', $roleIds)->get();
    }

    /**
     * Get the name of the roles associated with this model
     *
     * @return \Illuminate\Support\Collection
     */
    public function getRoleNames()
    {
        return $this->getRoles()->pluck('name');
    }

    /**
     * add single role
     *
     * @param  mixed $role
     * @return void
     */
    public function addRole($role)
    {
        $this->addRoles([$role]);
    }

    /**
     * add many roles
     *
     * @param  mixed $roles
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
        foreach ($roles as $role) {
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
     * @param  Role|string $role
     * @return void
     */
    public function delRole($role)
    {
        $this->delRoles([$role]);
    }

    /**
     * delete the given roles
     *
     * @param  mixed $roles
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
     * @param  mixed $role
     * @return void
     */
    public function setRole($role)
    {
        $this->setRoles([$role]);
    }

    /**
     * set the role associated with this model
     *
     * @param  mixed $roles
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
     * @param  Role|string $role
     * @return bool
     */
    public function hasRole($role)
    {
        if (gettype($role) == 'string')
            $role = self::Role()::where('name', $role)->first();
        if (!$role instanceof Role)
            throw new Exception('Role must be valid role');
        return $this->getBaseQuery()->where('role_id', $role->id)->exists();
    }

    /**
     * check whether this model has the given roles
     *
     * @param  mixed $role
     * @return bool
     */
    public function hasRoles($roles)
    {
        return $this->hasHowManyRoles($roles) == count($roles);
    }

    /**
     * check whether this model has any of the given roles
     *
     * @param  mixed $roles
     * @return bool
     */
    public function hasAnyRole($roles)
    {
        return $this->hasHowManyRoles($roles) > 0;
    }

    /**
     * add single role to many models
     *
     * @param mixed $role
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
     * @param mixed $roles
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
        foreach ($role_ids as $rid) {
            foreach ($user_ids as $uid) {
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
     * @param mixed $role
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
     * @param mixed $role
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
     * Get the given models along with their roles
     *
     * @param  mixed $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function withRoles($models)
    {
        // normalize models
        $users = self::normalizeModels($models);

        // get the name of the id column of the model
        $idCol = static::getIdColumn();

        // get the model ids
        $uids = $users->pluck($idCol);

        // get the association models
        $urs = UserRole::query()
            ->whereIn('user_id', $uids)
            ->get();

        // get the permission ids
        $rids = $urs->pluck('role_id');

        // get the roles
        $roles = Role::whereIn('id', $rids)->get();

        // build a map ID -> USER
        $id2user = [];
        foreach ($users as $u)
        {
            $uid = $u[$idCol];
            $id2user[$uid] = $u;
        }

        // build a map ID -> ROLE
        $id2role = [];
        foreach ($roles as $r)
        {
            $rid = $r->id;
            $id2role[$rid] = $r;
        }

        // initialize role array
        foreach ($users as $u)
            $u->roles = collect();

        foreach ($urs as $ur)
        {
            $rid = $ur->role_id;
            $uid = $ur->user_id;
            $u = $id2user[$uid];
            $r = $id2role[$rid];
            $u->roles->add($r);
        }

        // return the model
        return $users;
    }

    /**
     * Get the given users with their roles names
     *
     * @param  mixed $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function withRoleNames($models)
    {
        $users = static::withRoles($models);
        foreach ($users as $u)
            $u->roles = $u->roles->pluck('name');
        return $users;
    }

    /**
     * get how many of the given roles this model has
     *
     * @param  mixed $roles
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
            $roles = self::Role()::whereIn('name', $roles)->get();
            if ($roles->count() != $n)
                throw new Exception('One or more roles do not exist.');
        }
        if (! $roles->first() instanceof Role)
            throw new Exception('Roles must be valid roles');
        return $roles;
    }
}

?>