<?php

namespace Tests\App\Database\Seeders;

use Tests\App\Models\User;
use Tests\App\Models\Role;
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
        User::factory(15)->create();
        $users = User::all();
        $roles = Role::all();
        foreach ($users as $user)
        {
            $role = $roles->random();
            $user->addRole($role);
        }
    }
}
