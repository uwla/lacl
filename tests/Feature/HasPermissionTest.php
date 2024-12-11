<?php

namespace Tests\Feature;

use Tests\App\Database\Seeders\DatabaseSeeder;
use Tests\App\Models\Article;
use Tests\App\Models\Permission;
use Tests\App\Models\Role;
use Tests\App\Models\User;
use Tests\TestCase;
use Uwla\Lacl\Models\Permissionable;

class HasPermissionTest extends TestCase
{
    /**
     * How many permissions to create
     *
     * @var int
     */
    private $n = 20;
    private $m = 15;

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
     * Test addition of single permission
     *
     * @return void
     */
    public function test_add_permission()
    {
        $role = Role::factory()->createOne();
        $permission = Permission::factory()->createOne();

        // assert it currently does not have the permission
        $this->assertFalse(
            Permissionable::where([
                'permissionable_type' => $role::class,
                'permissionable_id' => $role->id,
                'permission_id' => $permission->id
            ])->exists()
        );

        // add permission
        $role->addPermission($permission);

        // assert it now has the permission
        $this->assertTrue(
            Permissionable::where([
                'permissionable_type' => $role::class,
                'permissionable_id' => $role->id,
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
        $role = Role::factory()->createOne();
        $permissions = Permission::factory($n)->create();
        $ids = $permissions->pluck('id');

        // assert it does not have the permissions
        $m = Permissionable::query()
            ->whereIn('permission_id', $ids)
            ->where('permissionable_id', $role->id)
            ->where('permissionable_type', $role::class)
            ->count();
        $this->assertEquals(0, $m);

        // add permission
        $role->addPermissions($permissions);

        // assert it now has the permissions
        $m = Permissionable::query()
            ->whereIn('permission_id', $ids)
            ->where('permissionable_id', $role->id)
            ->where('permissionable_type', $role::class)
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
        $role = Role::factory()->createOne();
        $user = User::factory()->createOne();
        $permissions = Permission::factory($n)->create();

        $user->addRole($role);
        $role->addPermissions($permissions);
        $role_permissions = $role->getPermissions();
        $user_permissions = $user->getPermissions();

        $this->assertTrue($permissions->diff($role_permissions)->isEmpty());
        $this->assertTrue($permissions->diff($user_permissions)->isEmpty());
    }

    /**
     * Test getting the permissions via MorphToMany.
     *
     * @return void
     */
    public function test_get_permissions_morph_to_many()
    {
        $n = $this->n;
        $role = Role::factory()->createOne();
        $permissions = Permission::factory($n)->create();
        $role->addPermissions($permissions);
        $role_permissions = $role->permissions;
        $this->assertTrue($permissions->diff($role_permissions)->isEmpty());
    }

    /**
     * Test having the given permission
     *
     * @return void
     */
    public function test_has_permission()
    {
        $role = Role::factory()->createOne();
        $user = User::factory()->createOne();
        $permission = Permission::factory()->createOne();
        $permission2 = Permission::factory()->createOne();

        $user->addRole($role);
        $this->assertFalse($role->hasPermission($permission));
        $this->assertFalse($user->hasPermission($permission));

        $role->addPermission($permission);

        $this->assertTrue($role->hasPermission($permission));
        $this->assertTrue($user->hasPermission($permission));
        $this->assertTrue($role->hasPermission($permission->name));
        $this->assertTrue($user->hasPermission($permission->name));

        $this->assertFalse($role->hasPermission($permission2));
        $this->assertFalse($user->hasPermission($permission2));
        $this->assertFalse($role->hasPermission($permission2->name));
        $this->assertFalse($user->hasPermission($permission2->name));
    }

    /**
     * Test dynamic permissions
     *
     * @return void
     */
    public function test_dynamic_permissions()
    {
        $role = Role::factory()->createOne();
        $user = User::factory()->createOne();
        Permission::create(['name' => 'sendEmails']);
        Permission::create(['name' => 'createPosts']);

        $user->addRole($role);

        // add the permissions
        $role->addPermissionToSendEmails();
        $role->addPermissionToCreatePosts();
        $this->assertTrue($role->hasPermissionToSendEmails());
        $this->assertTrue($user->hasPermissionToSendEmails());
        $this->assertTrue($role->hasPermissionToCreatePosts());
        $this->assertTrue($user->hasPermissionToCreatePosts());

        // delete the permissions
        $role->delPermissionToSendEmails();
        $role->delPermissionToCreatePosts();
        $this->assertFalse($role->hasPermissionToSendEmails());
        $this->assertFalse($user->hasPermissionToSendEmails());
        $this->assertFalse($role->hasPermissionToCreatePosts());
        $this->assertFalse($user->hasPermissionToCreatePosts());
    }

    /**
     * Test having the per-model permission dynamically,
     *
     * @return void
     */
    public function test_has_perModel_permission_dynamically()
    {
        // create models and permissions
        $role    = Role::factory()->createOne();
        $article = Article::factory()->createOne();
        $article->createCrudPermissions();

        // test the view permission
        $this->assertFalse($role->hasPermissionToView($article));
        $role->addPermissionToView($article);
        $this->assertTrue($role->hasPermissionToView($article));

        // try now with update permission
        $this->assertFalse($role->hasPermissionToUpdate($article));
        $role->addPermissionToUpdate($article);
        $this->assertTrue($role->hasPermissionToUpdate($article));

        // delete the permissions
        $role->delPermissionToView($article);
        $this->assertFalse($role->hasPermissionToView($article));
        $role->delPermissionToUpdate($article);
        $this->assertFalse($role->hasPermissionToUpdate($article));
    }

    /**
     * Test having all given permissions
     *
     * @return void
     */
    public function test_has_permissions()
    {
        $n = $this->n;
        $role = Role::factory()->createOne();
        $user = User::factory()->createOne();
        $permissions = Permission::factory($n)->create();

        $user->addRole($role);
        $this->assertFalse($role->hasPermissions($permissions));
        $this->assertFalse($user->hasPermissions($permissions));

        $role->addPermissions($permissions);
        $this->assertTrue($role->hasPermissions($permissions));
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
        $role = Role::factory()->createOne();
        $user = User::factory()->createOne();

        $permissions = Permission::factory($n)->create();
        $otherPermissions = Permission::factory($n)->create();
        $mixed = $permissions->merge($otherPermissions);

        $user->addRole($role);
        $role->addPermissions($permissions);

        $this->assertFalse($role->hasPermissions($mixed));
        $this->assertFalse($user->hasPermissions($mixed));

        $this->assertTrue($role->hasAnyPermission($mixed));
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
        $role = Role::factory()->createOne();
        $oldPermissions = Permission::factory($n)->create();
        $newPermissions = Permission::factory($n)->create();

        $role->addPermissions($oldPermissions);
        $this->assertTrue($role->hasPermissions($oldPermissions));

        $role->setPermissions($newPermissions);
        $this->assertTrue($role->hasPermissions($newPermissions));
        $this->assertFalse($role->hasAnyPermission($oldPermissions));
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

        $role = Role::factory()->createOne();
        $permissions = Permission::factory($n)->create();
        $toDel = $permissions->take($m);

        $role->addPermissions($permissions);
        $role->delPermissions($toDel);
        $this->assertEquals($role->countPermissions(), $n - $m);
        $this->assertFalse($role->hasAnyPermission($toDel));
    }

    /**
     * Test deleting all permissions
     *
     * @return void
     */
    public function test_del_all_permissions()
    {
        $n = $this->n;
        $role = Role::factory()->createOne();
        $permissions = Permission::factory($n)->create();

        $role->addPermissions($permissions);
        $this->assertEquals($role->countPermissions(), $n);
        $role->delAllPermissions();
        $this->assertEquals($role->countPermissions(), 0);
    }

    /**
     * Test addition and deletion of roles to many users
     *
     * @return void
     */
    public function test_mass_permission_attribution()
    {
        $n = 70;
        $m = 10;
        $permissions = Permission::factory($n)->create();
        $roles = Role::factory($m)->create();

        $pid = $permissions->pluck('id');
        $rid = $roles->pluck('id');
        $countPermissions = fn() => Permissionable::query()
            ->whereIn('permission_id', $pid)
            ->whereIn('permissionable_id', $rid)
            ->where('permissionable_type', $roles->first()::class)
            ->count();

        Role::addPermissionsToMany($permissions, $roles);
        $this->assertTrue($countPermissions() == $n * $m);
        $this->assertTrue($roles->random()->hasPermissions($permissions));
        Role::delPermissionsFromMany($permissions, $roles);
        $this->assertTrue($countPermissions() == 0);
        $this->assertFalse($roles->random()->hasAnyPermission($permissions));
    }

    /**
     * Test getting roles along with their permissions
     *
     * @return void
     */
    public function test_get_roles_with_permissions()
    {
        $n = $this->n;
        $m = $this->m;
        $roles = Role::factory($m)->create();
        $permissions = Permission::factory($n)->create();
        $roles->each(fn($r) => $r->addPermissions($permissions->random(1, $n)));

        $roles = Role::withPermissionNames($roles);
        foreach ($roles as $r) {
            $this->assertEquals($r->permissions, $r->getPermissionNames());
        }
    }
}