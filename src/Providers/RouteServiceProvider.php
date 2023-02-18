<?php

namespace Uwla\Lacl\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

use Uwla\Lacl\Http\Controllers\PermissionController;
use Uwla\Lacl\Http\Controllers\RoleController;
use Uwla\Lacl\Http\Controllers\UserController;
use Uwla\Lacl\Models\Permission;
use Uwla\Lacl\Models\Role;
use Uwla\Lacl\Models\User;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Route::model('role', Role::class);
        Route::model('user', User::class);
        Route::model('permission', Permission::class);

        Route::group(['middleware' => [SubstituteBindings::class, 'auth:sanctum']], function() {
            Route::apiResource('permission', PermissionController::class);
            Route::apiResource('role', RoleController::class);
            Route::apiResource('user', UserController::class);
        });
    }
}
