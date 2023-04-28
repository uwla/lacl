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
        $prefix = $permissionable::getPermissionPrefix();

        // check permission exists
        foreach (['view', 'update', 'delete'] as $action)
        {
            $permissionName = $prefix . '.' . $action;
            $attr['name'] = $permissionName;
            $this->assertTrue(Permission::where($attr)->exists());
        }

        // now, test the same thing but with generic permissions,
        // that is, we are not testing per-model permissions.
        $permissionable::createCrudPermissions();
        $attr = [
            'model' => $permissionable::class,
        ];
        foreach (['create', 'viewAny', 'updateAny', 'deleteAny'] as $action)
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

        // per model permissions
        $permission = $permissionable->createViewPermission();
        $this->assertTrue($permission->is($permissionable->getViewPermission()));
        $permission = $permissionable->createUpdatePermission();
        $this->assertTrue($permission->is($permissionable->getUpdatePermission()));
        $permission = $permissionable->createDeletePermission();
        $this->assertTrue($permission->is($permissionable->getDeletePermission()));

        // now, do the same but with the static permissions
        $permission = $permissionable::createCreatePermission();
        $this->assertTrue($permission->is($permissionable::getCreatePermission()));
        $permission = $permissionable::createViewAnyPermission();
        $this->assertTrue($permission->is($permissionable::getViewAnyPermission()));
        $permission = $permissionable::createUpdateAnyPermission();
        $this->assertTrue($permission->is($permissionable::getUpdateAnyPermission()));
        $permission = $permissionable::createDeleteAnyPermission();
        $this->assertTrue($permission->is($permissionable::getDeleteAnyPermission()));
    }

    /**
     * Test getting models
     *
     * @return void
     */
    public function test_getting_models()
    {
        $m1 = $this->newPermissionable();
        $m2 = $this->newPermissionable();
        $m3 = $this->newPermissionable();
        $user = User::factory()->createOne();

        $m1->createCrudPermissions();
        $m2->createCrudPermissions();
        $m3->createCrudPermissions();

        // test it gets the models if there is only one model
        $m1->attachUpdatePermission($user);
        $models = $user->getModels($m1::class);
        $this->assertCount(1, $models);
        $this->assertTrue($m1->id == $models[0]->id);

        // test it gets the models if there are many models
        $m2->attachViewPermission($user);
        $models = $user->getModels($m2::class);
        $this->assertCount(2, $models);
        $this->assertContains($m2->id, $models->pluck('id'));

        // test it gets the models based on permission name
        $models = $user->getModels($m2::class, 'view');
        $this->assertCount(1, $models);
        $this->assertTrue($m2->id == $models[0]->id);

        $m3->attachViewPermission($user);
        $models = $user->getModels($m3::class, 'view');
        $this->assertCount(2, $models);
        $this->assertContains($m3->id, $models->pluck('id'));
    }

    /**
     * Test deleting permissions
     *
     * @return void
     */
    public function test_deleting_permissions()
    {
        $permissionable = $this->newPermissionable();

        // test per model permissions
        $attr = [
            'model' => $permissionable::class,
            'model_id' => $permissionable->id,
        ];

        $permissionable->createCrudPermissions(); // view, update, delete
        $this->assertTrue(Permission::where($attr)->count() == 3);
        $permissionable->deleteCrudPermissions();
        $this->assertTrue(Permission::where($attr)->count() == 0);

        // now, with static permissions
        $attr = [
            'model' => $permissionable::class,
        ];

        $permissionable::createCrudPermissions(); // create, viewAny, updateAny, deleteAny
        $this->assertTrue(Permission::where($attr)->count() == 4);
        $permissionable::deleteCrudPermissions();
        $this->assertTrue(Permission::where($attr)->count() == 0);}

    /**
     * Test attaching and revoking permissions
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

        // DO THE EXACT SAME THING, BUT WITH STATIC PERMISSIONS..

        // create permissions
        $permissions = $permissionable::createCrudPermissions();

        // attach permissions to the user
        $permissionable::attachCrudPermissions($user);
        $permissionable::attachCrudPermissions($role);

        // validate
        $this->assertTrue($user->hasPermissions($permissions));
        $this->assertTrue($role->hasPermissions($permissions));

        // revoke
        $permissionable::revokeCrudPermissions($user);

        // validate
        $this->assertFalse($user->hasAnyPermission($permissions));
        $this->assertTrue($role->hasPermissions($permissions));
    }
}

?>
