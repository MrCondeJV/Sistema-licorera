<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsurePinAuthenticated;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\IdentifyTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            IdentifyTenant::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'wompi/webhook',
        ]);

        $middleware->alias([
            'tenant' => IdentifyTenant::class,
            'pin.auth' => EnsurePinAuthenticated::class,
            'role' => CheckRole::class,
            'superadmin' => EnsureSuperAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
