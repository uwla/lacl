<?php

namespace Uwla\Lacl\Database\Seeders;

use Uwla\Lacl\Models\Permission;
use Uwla\Lacl\Models\Role;
use Uwla\Lacl\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $admin = Role::create([
            'name' => 'admin',
            'description' => 'administrates everything'
        ]);
        $userManager = Role::create([
            'name' => 'user manager',
            'description' => 'manages users'
        ]);
        $manager = Role::create([
            'name' => 'chief manager',
            'description' => 'manages access control'
        ]);

        $adminPermissions = Permission::all();
        $userManagerPermissions = Permission::where('model', User::class)->get();
        $managerPermissions = Permission::whereIn('model', [User::class, Role::class])->get();

        $admin->addPermissions($adminPermissions);
        $userManager->addPermissions($userManagerPermissions);
        $manager->addPermissions($managerPermissions);
    }
}
