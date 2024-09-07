<?php

namespace Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;

abstract class TestCase extends BaseTestCase
{
    use WithFaker;

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array<\Illuminate\Support\ServiceProvider>
     */
    protected function getPackageProviders($app): array
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
    protected function defineDatabaseMigrations(): void
    {
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__ . '/' . '../database/migrations');
    }
}
