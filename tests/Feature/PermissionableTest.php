<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Article;
use Uwla\Lacl\Models\Permission;
use Uwla\Lacl\Models\Role;
use Uwla\Lacl\Models\User;

class PermissionableTest extends TestCase
{

    protected function newPermissionable()
    {
        return Article::factory()->createOne();
    }

    /**
     * Test starting with zero permissions
     *
     * @return void
     */
    public function test_start_with_zero_permissions()
    {
        $permissionable = $this->newPermissionable();

        $attr = [
            'model' => $permissionable::class,
            'model_id' => $permissionable->id,
        ];

        // asset no permission exist
        $this->assertFalse(Permission::where($attr)->exists());
    }

    /**
     * Test creation of permissions
     *
     * @return void
     */
    public function test_creating_permissions()
    {
        $permissionable = $this->newPermissionable();

        // create permissions
        $permissionable->createCrudPermissions();

        // attributes
        $attr = [
            'model' => $permissionable::class,
            'model_id' => $permissionable->id,
        ];
        $prefix = $permissionable->getPermissionPrefix();

        // check permission exists
        foreach (['view', 'update', 'delete'] as $action)
        {
            $permissionName = $prefix . '.' . $action;
            $attr['name'] = $permissionName;
            $this->assertTrue(Permission::where($attr)->exists());
        }
    }

    /**
     * Test getting permissions
     *
     * @return void
     */
    public function test_getting_permissions()
    {
        $permissionable = $this->newPermissionable();

        // view permission
        $permission = $permissionable->createViewPermission();
        $this->assertTrue($permission->is($permissionable->getViewPermission()));

        // update permission
        $permission = $permissionable->createUpdatePermission();
        $this->assertTrue($permission->is($permissionable->getUpdatePermission()));

        // delete permission
        $permission = $permissionable->createDeletePermission();
        $this->assertTrue($permission->is($permissionable->getDeletePermission()));
    }

    /**
     * Test deleting permissions
     *
     * @return void
     */
    public function test_deleting_permissions()
    {
        $permissionable = $this->newPermissionable();
        $attr = [
            'model' => $permissionable::class,
            'model_id' => $permissionable->id,
        ];

        $permissionable->createCrudPermissions();
        $this->assertTrue(Permission::where($attr)->count() == 3);

        $permissionable->deleteCrudPermissions();
        $this->assertTrue(Permission::where($attr)->count() == 0);
    }

    /**
     * Test attaching permissions
     *
     * @return void
     */
    public function test_attaching_revoking_permissions()
    {
        $permissionable = $this->newPermissionable();
        $user = User::factory()->createOne();
        $role = Role::factory()->createOne();

        // create permissions
        $permissions = $permissionable->createCrudPermissions();

        // attach permissions to the user
        $permissionable->attachCrudPermissions($user);
        $permissionable->attachCrudPermissions($role);

        // validate
        $this->assertTrue($user->hasPermissions($permissions));
        $this->assertTrue($role->hasPermissions($permissions));

        // revoke
        $permissionable->revokeCrudPermissions($user);

        // validate
        $this->assertFalse($user->hasAnyPermission($permissions));
        $this->assertTrue($role->hasPermissions($permissions));
    }
}

?>
