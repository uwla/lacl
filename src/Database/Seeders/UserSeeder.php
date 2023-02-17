<?php

namespace Uwla\Lacl\Database\Seeders;

use Uwla\Lacl\Models\User;
use Uwla\Lacl\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \Uwla\Lacl\Models\User::factory(15)->create();
        $users = User::all();
        $roles = Role::all();
        foreach ($users as $user)
        {
            $role = $roles->random(1)[0];
            $user->addRole($role);
        }
    }
}
