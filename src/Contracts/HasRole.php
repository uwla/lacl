<?php

namespace Uwla\Lacl\Contracts;

Interface HasRole
{
    /**
     * Get the roles associated with this model
     *
     * @return \Illuminate\Database\Eloquent\Collection<\Uwla\Lacl\Role>
    */
    public function getRoles();

    /**
     * Get the name of the roles associated with this model
     *
     * @return array<string>
    */
    public function getRoleNames();

    /**
     * add single role
     *
     * @param Uwla\Lacl\Role|string $role
     * @return void
    */
    public function addRole($role);

    /**
     * add many roles
     *
     * @param Uwla\Lacl\Role[]|string[] $roles
     * @return void
    */
    public function addRoles($roles);

    /**
     * delete single role
     *
     * @param Uwla\Lacl\Role|string $role
     * @return void
    */
    public function delRole($role);

    /**
     * delete the given roles
     *
     * @param Uwla\Lacl\Role[]|string[] $roles
     * @return void
    */
    public function delRoles($roles);

    /**
     * delete all roles associated with this model
     *
     * @return void
    */
    public function delAllRoles();

    /**
     * set a single role associated with this model
     *
     * @param Uwla\Lacl\Role|string $role
     * @return void
    */
    public function setRole($role);

    /**
     * set the role associated with this model
     *
     * @param Uwla\Lacl\Role[]|string[] $roles
     * @return void
    */
    public function setRoles($roles);

    /**
     * count how many roles this model has
     *
     * @return int
    */
    public function countRoles();

    /**
     * check whether this model has the given role
     *
     * @param Uwla\Lacl\Role]|string $role
     * @return bool
    */
    public function hasRole($role);

    /**
     * check whether this model has the given roles
     *
     * @param Uwla\Lacl\Role[]|string[] $role
     * @return bool
    */
    public function hasRoles($roles);

    /**
     * check whether this model has any of the given roles
     *
     * @param Uwla\Lacl\Role[]|string[] $roles
     * @return bool
    */
    public function hasAnyRoles($roles);
}

?>