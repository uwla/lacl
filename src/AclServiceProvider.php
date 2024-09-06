<?php

namespace Uwla\Lacl;

use Illuminate\Support\ServiceProvider;

class AclServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // publishes migrations
        $src = __DIR__ . '/' . '../database/migrations/create_acl_tables.php';
        $dest = $this->app->databasePath(
            'migrations/2023_03_03_000000_create_acl_tables.php'
        );
        $this->publishes([$src => $dest], 'migrations');
    }
}