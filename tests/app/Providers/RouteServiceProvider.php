<?php

namespace Tests\App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Tests\App\Http\Controllers\PermissionController;
use Tests\App\Http\Controllers\RoleController;
use Tests\App\Http\Controllers\UserController;
use Tests\App\Models\Permission;
use Tests\App\Models\Role;
use Tests\App\Models\User;

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
