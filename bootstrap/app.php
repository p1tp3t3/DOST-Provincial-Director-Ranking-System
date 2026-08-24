<?php

use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\User\EmployeeMiddleware;
use App\Http\Middleware\User\ProfileViewMiddleware;
use App\Http\Middleware\User\ProvincialAdminMiddleware;
use App\Http\Middleware\User\ProvincialSubAdminMiddleware;
use App\Http\Middleware\User\ProvincialDirectorMiddleware;
use App\Http\Middleware\User\ActivationStatusMiddleware;
use App\Http\Middleware\User\RegionalAdminMiddleware;
use App\Http\Middleware\User\SubAdminMiddleware;
use App\Http\Middleware\User\SuperAdminMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            CheckMaintenanceMode::class,
        ]);
        $middleware->alias([
            'activation'           => ActivationStatusMiddleware::class,
            'role'                 => RoleMiddleware::class,
            'super-admin'          => SuperAdminMiddleware::class,
            'sub-admin'            => SubAdminMiddleware::class,
            'regional-admin'       => RegionalAdminMiddleware::class,
            'provincial-admin'     => ProvincialAdminMiddleware::class,
            'provincial-sub-admin' => ProvincialSubAdminMiddleware::class,
            'provincial-director'  => ProvincialDirectorMiddleware::class,
            'employee'             => EmployeeMiddleware::class,
            'profile-view'         => ProfileViewMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
