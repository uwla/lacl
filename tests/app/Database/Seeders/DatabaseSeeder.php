<?php

namespace Tests\App\Database\Seeders;

use Tests\App\Database\Seeders\UserSeeder;
use Tests\App\Database\Seeders\RoleSeeder;
use Tests\App\Database\Seeders\PermissionSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $seeders = [
            PermissionSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
        ];
        $this->call($seeders);
    }
}
