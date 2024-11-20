<?php

namespace Tests\App\Database\Seeders;

use Tests\App\Models\Permission;
use Tests\App\Models\Role;
use Tests\App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $resources = [
            "user" => User::class,
            "role" => Role::class,
            "permission" => Permission::class,
        ];
        $actions = ["create", "delete", "forceDelete", "update", "view"];
        $actionSuffix = "Any";
        $permissions = [];

        foreach ($resources as $name => $resource)
        {
            foreach ($actions as $action)
            {
                $actionName = $name . "." . $action . $actionSuffix;
                $permissions[] = [
                    'name' => $actionName,
                    'model_type' => $resource,
                ];
            }
        }

        Permission::insert($permissions);
    }
}