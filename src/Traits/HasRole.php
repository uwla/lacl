<?php

namespace Uwla\Lacl\Traits;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Uwla\Lacl\Models\Role;
use Uwla\Lacl\Models\RoleModel;

trait HasRole
{
    use HasPermission;

    /*
     * Get a base query to keep building on it
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function getBaseQuery(): Builder
    {
        return RoleModel::where([
            'model_type' => $this::class,
            'model_id' => $this->getModelId(),
        ]);
    }

    /*
     * Get the roles associated with this model
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRoles(): Collection
    {
        $role_ids = $this->getBaseQuery()->pluck('role_id');
        return static::Role()::whereIn('id', $role_ids)->get();
    }

    /*
     * Get the name of the roles associated with this model
     *
     * @return \Illuminate\Support\Collection
     */
    public function getRoleNames(): Collection
    {
        return $this->getRoles()->pluck('name');
    }

    /*
     * add single role
     *
     * @param  string|Role $role
     * @return void
     */
    public function addRole($role): void
    {
        $this->addRoles([$role]);
    }

    /*
     * add many roles
     *
     * @param  array|Collection $roles
     * @return void
     */
    public function addRoles($roles): void
    {
        $roles = static::normalizeRoles($roles);
        $model = $this::class;
        $model_id = $this->getModelId();
        $toAdd = [];
        foreach ($roles as $role) {
            $toAdd[] = [
                'model_type'   => $model,
                'model_id' => $model_id,
                'role_id'  => $role->id,
            ];
        }
        RoleModel::insert($toAdd);
    }

    /*
     * delete single role
     *
     * @param  Role|string $role
     * @return void
     */
    public function delRole($role): void
    {
        $this->delRoles([$role]);
    }

    /*
     * delete the given roles
     *
     * @param  array|Collection $roles
     * @return void
     */
    public function delRoles($roles): void
    {
        $roles = static::normalizeRoles($roles);
        $ids = $roles->pluck('id');
        $this->getBaseQuery()->whereIn('role_id', $ids)->delete();
    }

    /*
     * delete all roles associated with this model
     *
     * @return void
     */
    public function delAllRoles(): void
    {
        $this->getBaseQuery()->delete();
    }

    /*
     * set a single role associated with this model
     *
     * @param  name|Collection $role
     * @return void
     */
    public function setRole($role): void
    {
        $this->setRoles([$role]);
    }

    /*
     * set the role associated with this model
     *
     * @param  array|Collection $roles
     * @return void
     */
    public function setRoles($roles): void
    {
        $this->delAllRoles();
        $this->addRoles($roles);
    }

    /*
     * count how many roles this model has
     *
     * @return int
     */
    public function countRoles(): int
    {
        return $this->getBaseQuery()->count();
    }

    /*
     * check whether this model has the given role
     *
     * @param  Role|string $role
     * @return bool
     */
    public function hasRole($role): bool
    {
        if (gettype($role) == 'string')
            $role = static::Role()::where('name', $role)->first();
        if (!$role instanceof Role)
            throw new Exception('Role must be valid role');
        return $this->getBaseQuery()->where('role_id', $role->id)->exists();
    }

    /*
     * check whether this model has the given roles
     *
     * @param  array|Collection $roles
     * @return bool
     */
    public function hasRoles($roles): bool
    {
        return $this->hasHowManyRoles($roles) == count($roles);
    }

    /*
     * check whether this model has any of the given roles
     *
     * @param  array|Collection $roles
     * @return bool
     */
    public function hasAnyRole($roles): bool
    {
        return $this->hasHowManyRoles($roles) > 0;
    }

    /*
     * add single role to many models
     *
     * @param array|Collection $role
     * @param \Illuminate\Database\Eloquent\Collection $models
     * @return void
     */
    public static function addRoleToMany($role, $models): void
    {
        static::addRolesToMany([$role], $models);
    }

    /*
     * add many roles to many models
     *
     * @param array|Collection $roles
     * @param \Illuminate\Database\Eloquent\Collection $models
     * @return void
     */
    public static function addRolesToMany($roles, $models): void
    {
        $roles = static::normalizeRoles($roles);
        $role_ids = $roles->pluck('id');
        $model_ids = $models->pluck(static::getIdColumn());
        $model = static::class;
        $toCreate = [];
        foreach ($role_ids as $rid) {
            foreach ($model_ids as $mid) {
                $toCreate[] = [
                    'model_type' => $model,
                    'model_id' => $mid,
                    'role_id' => $rid,
                ];
            }
        }
        RoleModel::insert($toCreate);
    }

    /*
     * delete a single role from many models
     *
     * @param string|Role $role
     * @param \Illuminate\Database\Eloquent\Collection $models
     * @return void
     */
    public static function delRoleFromMany($role, $models): void
    {
        static::delRolesFromMany([$role], $models);
    }

    /*
     * delete many roles from many models
     *
     * @param array|Collection $roles
     * @param \Illuminate\Database\Eloquent\Collection $models
     * @return void
     */
    public static function delRolesFromMany($roles, $models): void
    {
        $roles = static::normalizeRoles($roles);
        $rids = $roles->pluck('id');
        $uids = $models->pluck(static::getIdColumn());
        RoleModel::query()
            ->whereIn('role_id', $rids)
            ->whereIn('model_id', $uids)
            ->where('model_type', static::class)
            ->delete();
    }

    /*
     * Get the given models along with their roles
     *
     * @param  array|Collection $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function withRoles($models): Collection
    {
        $models = static::normalizeModels($models);
        $idColumn = static::getIdColumn();
        $mids = $models->pluck($idColumn);
        $role_models = RoleModel::query()
            ->where('model_type', static::class)
            ->whereIn('model_id', $mids)
            ->get();
        $role_ids = $role_models->pluck('role_id');
        $roles = Role::whereIn('id', $role_ids)->get();

        // build a map ID -> USER
        $id2model = [];
        foreach ($models as $model) {
            $model_id = $model[$idColumn];
            $id2model[$model_id] = $model;
        }

        // build a map ID -> ROLE
        $id2role = [];
        foreach ($roles as $role) {
            $role_id = $role->id;
            $id2role[$role_id] = $role;
        }

        // initialize role array
        foreach ($models as $model)
            $model->roles = collect();

        foreach ($role_models as $role_model) {
            $model_id = $role_model->model_id;
            $role_id = $role_model->role_id;
            $model = $id2model[$model_id];
            $role = $id2role[$role_id];
            $model->roles->add($role);
        }

        return $models;
    }

    /*
     * Get the given models with their roles names
     *
     * @param  array|Collection $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function withRoleNames($models): Collection
    {
        $models = static::withRoles($models);
        foreach ($models as $model)
            $model->roles = $model->roles->pluck('name');
        return $models;
    }

    /*
     * Get how many of the given roles this model has
     *
     * @param  name|Collection $roles
     * @return int
     */
    private function hasHowManyRoles($roles): int
    {
        $roles = static::normalizeRoles($roles);
        $ids = $roles->pluck('id');
        $matchedRoles = $this->getBaseQuery()->whereIn('role_id', $ids)->get();
        return $matchedRoles->count();
    }

    /*
     * Normalize $roles into an Eloquent Collection of Role
     *
     * @param  array|Collection $roles
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private static function normalizeRoles($roles): Collection
    {
        if (is_array($roles))
            $roles = collect($roles);
        if (!$roles instanceof Collection)
            throw new Exception('Roles must be collection or array');
        $n = $roles->count();
        if ($n == 0)
            throw new Exception('Roles must not be empty');
        if (is_string($roles->first())) {
            $roles = static::Role()::whereIn('name', $roles)->get();
            if ($roles->count() != $n)
                throw new Exception('One or more roles do not exist.');
        }
        if (!$roles->first() instanceof Role)
            throw new Exception('Roles must be valid roles');
        return $roles;
    }
}