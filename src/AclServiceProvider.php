<?php

namespace Uwla\Lacl;

use Illuminate\Support\ServiceProvider;

class AclServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // publishes migrations
        $src = __DIR__ . '/' . '../database/migrations/create_acl_tables.php';
        $now = date('Y_m_d_His', time());
        $dest = $this->app->databasePath(
            'database/migrations/' . $now . '_create_acl_tables.php'
        );
        $this->publishes([
            $src => $dest
        ], 'migrations');
    }
}
