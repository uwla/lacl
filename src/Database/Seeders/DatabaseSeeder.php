<?php

namespace Uwla\Lacl\Database\Seeders;

use Uwla\Lacl\Database\Seeders\UserSeeder;
use Uwla\Lacl\Database\Seeders\RoleSeeder;
use Uwla\Lacl\Database\Seeders\PermissionSeeder;
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
