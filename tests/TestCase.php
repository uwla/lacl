<?php

namespace Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array<\Illuminate\Support\ServiceProvider>
     */
    protected function getPackageProviders($app)
    {
        return [
            'Laravel\Sanctum\SanctumServiceProvider', // authentication
            'Tests\App\Providers\RouteServiceProvider', // authorization
        ];
    }

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__ . '/' . '../database/migrations');
    }
}
