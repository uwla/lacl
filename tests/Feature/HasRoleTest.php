<?php

namespace Tests\Feature;

use Tests\TestCase;
use Uwla\Lacl\Models\User;
use Uwla\Lacl\Models\Role;
use Uwla\Lacl\Models\UserRole;

class HasRoleTest extends TestCase
{
    /**
     * How many roles to create
     *
     * @var int
     */
    private $n = 15;
    private $m = 8;

    /**
     * Test addition of single role
     *
     * @return void
     */
    public function test_add_role()
    {
        $user = User::factory()->createOne();
        $role = Role::factory()->createOne();

        $user->addRole($role);
        $this->assertTrue(
            UserRole::where([
                'user_id' => $user->id,
                'role_id' => $role->id
            ])->exists()
        );
    }

    /**
     * Test addition of many roles
     *
     * @return void
     */
    public function test_add_roles()
    {
        $user = User::factory()->createOne();
        $roles = Role::factory($this->n)->create();

        $user->addRoles($roles);
        $ids = UserRole::query()
            ->where('user_id', $user->id)
            ->get()->pluck('role_id');
        $user_roles = Role::whereIn('id', $ids)->get();
        $this->assertTrue($roles->diff($user_roles)->isEmpty());
    }

    /**
     * Test getting the roles
     *
     * @return void
     */
    public function test_get_roles()
    {
        $user = User::factory()->createOne();
        $roles = Role::factory($this->n)->create();

        $user->addRoles($roles);
        $user_roles = $user->getRoles();
        $this->assertTrue($roles->diff($user_roles)->isEmpty());
    }

    /**
     * Test having given role
     *
     * @return void
     */
    public function test_has_role()
    {
        $user = User::factory()->createOne();
        $role = Role::factory()->createOne();
        $otherRole = Role::factory()->createOne();

        $user->addRole($role);
        $this->assertTrue($user->hasRole($role));
        $this->assertFalse($user->hasRole($otherRole));
    }

    /**
     * Test having all given roles
     *
     * @return void
     */
    public function test_has_roles()
    {
        $user = User::factory()->createOne();
        $roles = Role::factory($this->n)->create();
        $otherRoles = Role::factory($this->m)->create();

        $user->addRoles($roles);
        $this->assertTrue($user->hasRoles($roles));
        $this->assertFalse($user->hasRoles($otherRoles));
    }

    /**
     * Test having any of the given roles
     *
     * @return void
     */
    public function test_has_any_roles()
    {
        $user = User::factory()->createOne();
        $roles = Role::factory($this->n)->create();
        $otherRoles = Role::factory($this->m)->create();
        $mixed = $roles->merge($otherRoles)->shuffle();

        $user->addRoles($roles);
        $this->assertTrue($user->hasAnyRoles($mixed));
    }

    /**
     * Test setting single role
     *
     * @return void
     */
    public function test_set_role()
    {
        $user = User::factory()->createOne();
        $roles = Role::factory($this->n)->create();
        $role = $roles->random(1)[0];

        // old roles
        $user->addRoles($roles);
        $user->setRole($role);
        $this->assertTrue($user->hasRole($role));
        $this->assertFalse($user->hasRoles($roles));
    }

    /**
     * Test setting many roles
     *
     * @return void
     */
    public function test_set_roles()
    {
        $user = User::factory()->createOne();
        $oldRoles = Role::factory($this->n)->create();
        $newRoles = Role::factory($this->m)->create();

        // old roles
        $user->addRoles($oldRoles);
        $this->assertTrue($user->hasRoles($oldRoles));

        // new roles
        $user->setRoles($newRoles);
        $this->assertTrue($user->hasRoles($newRoles));
        $this->assertFalse($user->hasRoles($oldRoles));
    }

    /**
     * Test deletion of some role
     *
     * @return void
     */
    public function test_del_roles()
    {
        $n = $this->n; $m = $this->m;
        $user = User::factory()->createOne();
        $roles = Role::factory($n)->create();
        $toDel = $roles->random($m);

        $user->addRoles($roles);
        $user->delRoles($toDel);
        $this->assertEquals($user->countRoles(), $n - $m);
    }

    /**
     * Test deletion of all roles
     *
     * @return void
     */
    public function test_del_all_roles()
    {
        $n = $this->n;
        $user = User::factory()->createOne();
        $roles = Role::factory($n)->create();

        $user->addRoles($roles);
        $this->assertEquals($user->countRoles(), $n);
        $user->delAllRoles();
        $this->assertEquals($user->countRoles(), 0);
    }

    /**
     * Test addition and deletion of roles to many users
     *
     * @return void
     */
    public function test_mass_role_attribution()
    {
        $n = 70;
        $m = 8;
        $users = User::factory($n)->create();
        $roles = Role::factory($m)->create();


        $uid = $users->pluck('id');
        $rid = $roles->pluck('id');
        $f = fn() => UserRole::query()
            ->whereIn('user_id', $uid)
            ->whereIn('role_id', $rid)
            ->count();

        User::addRolesToMany($roles, $users);
        $this->assertTrue($f() == $n * $m);
        $this->assertTrue($users->random()->hasRoles($roles));
        User::delRolesFromMany($roles, $users);
        $this->assertTrue($f() == 0);
        $this->assertFalse($users->random()->hasAnyRoles($roles));
    }
}
