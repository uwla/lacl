<?php

namespace Tests\Feature;

use Tests\TestCase;
use Uwla\Lacl\Models\User;
use Uwla\Lacl\Models\Role;
use Uwla\Lacl\Models\Permission;
use Uwla\Lacl\Models\RolePermission;
use Uwla\Lacl\Database\Seeders\DatabaseSeeder;

class HasPermissionPerUserTest extends TestCase
{
    /**
     * How many permissions to create
     *
     * @var int
     */
    private $n = 12;
    private $m = 7;

    /**
     * Set up the test instance
     *
     * @return void
    */
    public function setUp() : void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /**
     * Test addition of single permission to user
     *
     * @return void
     */
    public function test_add_permission()
    {
        $user = User::factory()->createOne();
        $permission = Permission::factory()->createOne();

        // assert the unique user role does not exist
        // which implies the user has no permission
        $roleName = $user::class . ':' . $user->id;
        $this->assertFalse(
            Role::where('name', $roleName)->exists()
        );

        // add permission, which in turn creates unique user role
        $user->addPermission($permission);

        // assert the user role exists
        $this->assertTrue(
            Role::where('name', $roleName)->exists()
        );

        // assert it now has the permission
        $role = Role::where('name', $roleName)->first();
        $this->assertTrue(
            RolePermission::where([
                'role_id' => $role->id,
                'permission_id' => $permission->id
            ])->exists()
        );
    }


    /**
     * Test addition of many permissions
     *
     * @return void
     */
    public function test_add_permissions()
    {
        $n = $this->n;
        $user = User::factory()->createOne();
        $permissions = Permission::factory($n)->create();
        $ids = $permissions->pluck('id');

        // assert the unique user role does not exist
        // which implies the user has no permission
        $roleName = $user::class . ':' . $user->id;
        $this->assertFalse(
            Role::where('name', $roleName)->exists()
        );

        // add permission, which in turn creates unique user role
        $user->addPermissions($permissions);

        // assert the user role exists
        $this->assertTrue(
            Role::where('name', $roleName)->exists()
        );

        // assert it now has the permissions
        $role = Role::where('name', $roleName)->first();
        $m =RolePermission::query()
            ->whereIn('permission_id', $ids)
            ->where('role_id', $role->id)
            ->count();
        $this->assertEquals($n, $m);
    }

    /**
     * Test getting the permissions
     *
     * @return void
     */
    public function test_get_permissions()
    {
        $n = $this->n;
        $user = User::factory()->createOne();
        $permissions = Permission::factory($n)->create();
        $user->addPermissions($permissions);
        $user_permissions = $user->getPermissions();
        $this->assertTrue($permissions->diff($user_permissions)->isEmpty());
    }

    /**
     * Test having the given permission
     *
     * @return void
     */
    public function test_has_permission()
    {
        $user = User::factory()->createOne();
        $permission = Permission::factory()->createOne();
        $this->assertFalse($user->hasPermission($permission));
        $user->addPermission($permission);
        $this->assertTrue($user->hasPermission($permission));
    }

    /**
     * Test having all given permissions
     *
     * @return void
     */
    public function test_has_permissions()
    {
        $n = $this->n;
        $user = User::factory()->createOne();
        $permissions = Permission::factory($n)->create();
        $this->assertFalse($user->hasAnyPermission($permissions));
        $user->addPermissions($permissions);
        $this->assertTrue($user->hasPermissions($permissions));
    }

    /**
     * Test having any of the given permissions
     *
     * @return void
     */
    public function test_has_any_permission()
    {
        $n = $this->n;
        $user = User::factory()->createOne();
        $permissions = Permission::factory($n)->create();
        $otherPermissions = Permission::factory($n)->create();
        $mixed = $permissions->merge($otherPermissions);
        $user->addPermissions($permissions);
        $this->assertFalse($user->hasPermissions($mixed));
        $this->assertTrue($user->hasAnyPermission($mixed));
    }

    /**
     * Test setting the permissions
     *
     * @return void
     */
    public function test_set_permissions()
    {
        $n = $this->n;
        $user = User::factory()->createOne();
        $oldPermissions = Permission::factory($n)->create();
        $newPermissions = Permission::factory($n)->create();
        $user->addPermissions($oldPermissions);
        $this->assertTrue($user->hasPermissions($oldPermissions));
        $user->setPermissions($newPermissions);
        $this->assertTrue($user->hasPermissions($newPermissions));
        $this->assertFalse($user->hasAnyPermission($oldPermissions));
    }

    /**
     * Test deleting the given permissions
     *
     * @return void
     */
    public function test_del_permissions()
    {
        $n = $this->n;
        $m = $this->m;

        $user = User::factory()->createOne();
        $permissions = Permission::factory($n)->create();
        $toDel = $permissions->take($m);

        $user->addPermissions($permissions);
        $user->delPermissions($toDel);
        $this->assertEquals($user->countPermissions(), $n - $m);
        $this->assertFalse($user->hasAnyPermission($toDel));
    }

    /**
     * Test deleting all permissions
     *
     * @return void
     */
    public function test_del_all_permissions()
    {
        $n = $this->n;
        $user = User::factory()->createOne();
        $permissions = Permission::factory($n)->create();

        $user->addPermissions($permissions);
        $this->assertEquals($user->countPermissions(), $n);
        $user->delAllPermissions();
        $this->assertEquals($user->countPermissions(), 0);
    }
}

