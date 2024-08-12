<?php

namespace Tests\Feature;

use Tests\App\Database\Seeders\DatabaseSeeder;
use Tests\App\Models\Permission;
use Tests\App\Models\User;
use Tests\TestCase;

class HasPermissionPerUserTest extends TestCase
{
    /**
     * How many permissions to create
     *
     * @var int
     */
    private $n = 15;

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

        // assert the user does not have the permission
        $this->assertFalse($user->hasPermission($permission));

        // add permission
        $user->addPermission($permission);

        // assert the user has the permission
        $this->assertTrue($user->hasPermission($permission));
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

        // assert the user does not have the permissions
        $this->assertFalse($user->hasAnyPermission($permissions));

        // add permission, which in turn creates unique user role
        $user->addPermissions($permissions);

        // assert the user does have the permissions
        $this->assertTrue($user->hasPermissions($permissions));
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
        $m = $this->n;
        $n = $m * 3;

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