<?php

namespace Tests\App\Database\Seeders;

use Tests\App\Models\Permission;
use Tests\App\Models\Role;
use Tests\App\Models\User;
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
