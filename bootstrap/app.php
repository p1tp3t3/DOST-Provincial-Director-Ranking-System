<?php

use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\User\EmployeeMiddleware;
use App\Http\Middleware\User\ProfileViewMiddleware;
use App\Http\Middleware\User\ProvincialAdminMiddleware;
use App\Http\Middleware\User\ProvincialDirectorMiddleware;
use App\Http\Middleware\User\ActivationStatusMiddleware;
use App\Http\Middleware\User\SubAdminMiddleware;
use App\Http\Middleware\User\SuperAdminMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;


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
            'provincial-admin'     => ProvincialAdminMiddleware::class,
            'provincial-director'  => ProvincialDirectorMiddleware::class,
            'employee'             => EmployeeMiddleware::class,
            'profile-view'         => ProfileViewMiddleware::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // KPI reporting reminders (browser push). Both commands also guard against
        // sending twice, so an extra manual run doesn't repeat a reminder.
        // 3 days and 1 day before a quarter's deadline: regional directors,
        // provincial admins and provincial directors.
        $schedule->command('app:check-kpi-edit-submission-deadline-command')
                 ->dailyAt('08:00')
                 ->timezone('Asia/Manila')
                 ->withoutOverlapping();

        // Once a week: provincial admins and directors with blank accomplishments.
        $schedule->command('app:notify-missing-accomplishments')
                 ->weeklyOn(1, '08:00') // Mondays
                 ->timezone('Asia/Manila')
                 ->withoutOverlapping();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Saves that fail show what's wrong in plain words, never SQL/PHP text.
        \App\Exceptions\FriendlyErrors::register($exceptions);
    })->create();
