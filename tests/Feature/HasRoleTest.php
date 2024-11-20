<?php

namespace Tests\Feature;

use Tests\App\Models\Role;
use Tests\App\Models\User;
use Tests\TestCase;
use Uwla\Lacl\Models\Roleable;

class HasRoleTest extends TestCase
{
    /**
     * How many users to create
     *
     * @var int
     */
    private $n = 80;

    /**
     * How many roles to create
     *
     * @var int
     */
    private $m = 12;

    /**
     * Test addition of single role
     *
     * @return void
     */
    public function test_add_role(): void
    {
        $user = User::factory()->createOne();
        $role = Role::factory()->createOne();

        $user->addRole($role);
        $this->assertTrue(
            Roleable::where([
                'roleable_type' => $user::class,
                'roleable_id' => $user->id,
                'role_id' => $role->id
            ])->exists()
        );
    }

    /**
     * Test addition of many roles
     *
     * @return void
     */
    public function test_add_roles(): void
    {
        $user = User::factory()->createOne();
        $roles = Role::factory($this->m)->create();

        $user->addRoles($roles);
        $ids = Roleable::query()
            ->where([
                'roleable_id' => $user->id,
                'roleable_type' => $user::class
            ])->pluck('role_id');
        $user_roles = Role::whereIn('id', $ids)->get();
        $this->assertTrue($roles->diff($user_roles)->isEmpty());
    }

    /**
     * Test getting the roles
     *
     * @return void
     */
    public function test_get_roles(): void
    {
        $user = User::factory()->createOne();
        $roles = Role::factory($this->m)->create();

        $user->addRoles($roles);
        $user_roles = $user->getRoles();
        $this->assertTrue($roles->diff($user_roles)->isEmpty());
    }

    /**
     * Test having given role
     *
     * @return void
     */
    public function test_has_role(): void
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
    public function test_has_roles(): void
    {
        $user = User::factory()->createOne();
        $roles = Role::factory($this->m)->create();
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
    public function test_has_any_roles(): void
    {
        $user = User::factory()->createOne();
        $roles = Role::factory($this->m)->create();
        $otherRoles = Role::factory($this->m)->create();
        $mixed = $roles->merge($otherRoles)->shuffle();

        $user->addRoles($roles);
        $this->assertTrue($user->hasAnyRole($mixed));
    }

    /**
     * Test setting single role
     *
     * @return void
     */
    public function test_set_role(): void
    {
        $user = User::factory()->createOne();
        $roles = Role::factory($this->m)->create();
        $role = $roles->random();

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
    public function test_set_roles(): void
    {
        $user = User::factory()->createOne();
        $oldRoles = Role::factory($this->m)->create();
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
    public function test_del_roles(): void
    {
        $m = $this->m;
        $n = $m * 5;
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
    public function test_del_all_roles(): void
    {
        $m = $this->m;
        $user = User::factory()->createOne();
        $roles = Role::factory($m)->create();

        $user->addRoles($roles);
        $this->assertEquals($user->countRoles(), $m);
        $user->delAllRoles();
        $this->assertEquals($user->countRoles(), 0);
    }

    /**
     * Test addition and deletion of roles to many users
     *
     * @return void
     */
    public function test_mass_role_attribution(): void
    {
        $n = $this->n;
        $m = $this->m;
        $users = User::factory($n)->create();
        $roles = Role::factory($m)->create();

        $uid = $users->pluck('id');
        $rid = $roles->pluck('id');
        $f = fn() => Roleable::query()
            ->whereIn('role_id', $rid)
            ->whereIn('roleable_id', $uid)
            ->where('roleable_type', $users->first()::class)
            ->count();

        User::addRolesToMany($roles, $users);
        $this->assertTrue($f() == $n * $m);
        $this->assertTrue($users->random()->hasRoles($roles));
        User::delRolesFromMany($roles, $users);
        $this->assertTrue($f() == 0);
        $this->assertFalse($users->random()->hasAnyRole($roles));
    }

    /**
     * Test getting users along with their roles
     *
     * @return void
     */
    public function test_get_users_with_their_roles(): void
    {
        $n = $this->n;
        $m = $this->m;
        $users = User::factory($n)->create();
        $roles = Role::factory($m)->create();
        $users->each(fn($u) => $u->addRoles($roles->random(2, $m)));

        $this->assertTrue(true);
        $users = User::withRoleNames($users);
        foreach ($users as $u)
            $this->assertEquals($u->roles, $u->getRoleNames());
    }
}