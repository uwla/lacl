<?php

namespace Uwla\Lacl;

use Illuminate\Support\ServiceProvider;

class AclServiceProvider extends ServiceProvider
{
    public function register()
    {
        // if (! $this->app->runningInConsole())
        //     return;

        // publishes migration
        $stub = '../database/migrations/create_acl_tables.php.stub';
        $src  = __DIR__ . '/' . $stub;
        $dest = database_path('migrations/' . date('Y_m_d_His', time()) . '_create_acl_tables.php');
        $this->publishes([
            $src => $dest
        ], 'migrations');
    }

    public function boot()
    {
        //
    }
}
