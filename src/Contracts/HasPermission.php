<?php

namespace Uwla\Lacl\Contracts;

Interface HasPermission
{
    /**
     * add single permission
     *
     * @param mixed $permission
     * @return void
     */
    public function addPermission($permission);

    /**
     * add many permissions
     *
     * @param mixed $permissions
     * @return void
     */
    public function addPermissions($permissions);

    /**
     * revoke a permission associated with this role
     *
     * @param mixed $permission
     * @return void
     */
    public function delPermission($permission);

    /**
     * revoke the given permissions associated with this role
     *
     * @param mixed $permissions
     * @return void
     */
    public function delPermissions($permissions);

    /**
     * revoke all permissions associated with this role
     *
     * @param mixed $permissions
     * @return void
     */
    public function delAllPermissions();

    /**
     * set the permissions associated with this role
     *
     * @param mixed $permissions
     * @return void
      */
    public function setPermissions($permissions);

    /**
     * get the permissions associated with this object
     *
     * @return \Illuminate\Database\Eloquent\Collection<\Uwla\Lacl\Models\Permission>
      */
    public function getPermissions();

    /**
     * get the permissions associated with this user only, not with its roles.
     *
     * @return \Illuminate\Database\Eloquent\Collection<\Uwla\Lacl\Models\Permission>
     */
    public function getUserPermissions();

    /**
     * get the name of the permissions associated with this object
     *
     * @return array<string>
     */
    public function getPermissionNames();

    /**
     * check whether this object has the given permission
     *
     * @param mixed  $permission
     * @param string $resource
     * @param mixed  $id
     * @return bool
     */
    public function hasPermission($permission, $resource=null, $id=null);

    /**
     * check whether this object has the given permissions
     *
     * @param mixed  $permission
     * @param string $resource
     * @param mixed  $ids
     * @return bool
     */
    public function hasPermissions($permissions, $resource=null, $ids=null);

    /**
     * check whether this object has any of the given permissions
     *
     * @param mixed  $permission
     * @param string $resource
     * @param mixed  $ids
     * @return bool
     */
    public function hasAnyPermission($permissions, $resource=null, $ids=null);

    /**
     * get how many permissions this object has
     *
     * @return int
     */
    public function countPermissions();

    /**
     * add single permission to many models
     *
     * @param \Uwla\Lacl\Permission|string $permission
     * @param \Illuminate\Database\Eloquent\Collection $models
     * @return void
    */
    public static function addPermissionToMany($permission, $models);

    /**
     * add many permissions to many models
     *
     * @param \Uwla\Lacl\Permission[]|string[] $permission
     * @param \Illuminate\Database\Eloquent\Collection $models
     * @return void
    */
    public static function addPermissionsToMany($permissions, $models);

   /**
     * delete a single permission from many models
     *
     * @param \Uwla\Lacl\Permission|string $permission
     * @param \Illuminate\Database\Eloquent\Collection $models
     * @return void
    */
    public static function delPermissionFromMany($permission, $models);

    /**
     * delete many permissions from many models
     *
     * @param \Uwla\Lacl\Permission[]|string[] $permission
     * @param \Illuminate\Database\Eloquent\Collection $models
     * @return void
    */
    public static function delPermissionsFromMany($permissions, $models);
}
