<?php

namespace Uwla\Lacl;

use Illuminate\Support\ServiceProvider;

class AclServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
