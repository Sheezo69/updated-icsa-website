<?php

use App\Http\Middleware\AuthenticateAdmin;
use App\Http\Middleware\EnsureAdminOwner;
use App\Http\Middleware\EnsureAdminPermission;
use App\Http\Middleware\RedirectIfAdminAuthenticated;
use App\Http\Middleware\TrackWebsiteAnalytics;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.auth' => AuthenticateAdmin::class,
            'admin.owner' => EnsureAdminOwner::class,
            'admin.permission' => EnsureAdminPermission::class,
            'admin.guest' => RedirectIfAdminAuthenticated::class,
            'analytics' => TrackWebsiteAnalytics::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
