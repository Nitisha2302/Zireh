<?php

use App\Http\Middleware\AdminAuthMiddleware;
use App\Http\Middleware\EnsureAdminRole;
use App\Http\Middleware\EnsureAlifProviderIsAuthorized;
use App\Http\Middleware\EnsureCustomerIsActive;
use App\Http\Middleware\LogAlifProviderRequest;
use App\Http\Middleware\SetApplicationLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', SetApplicationLocale::class);
        $middleware->appendToGroup('api', SetApplicationLocale::class);

        $middleware->alias([
            'is_auth' => AdminAuthMiddleware::class,
            'admin.role' => EnsureAdminRole::class,
            'set_locale' => SetApplicationLocale::class,
            'customer.active' => EnsureCustomerIsActive::class,
            'alif.log' => LogAlifProviderRequest::class,
            'alif.auth' => EnsureAlifProviderIsAuthorized::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
