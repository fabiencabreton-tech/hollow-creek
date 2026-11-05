<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureStaffHasTwoFactor;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TouchLastSeen;
use App\Http\Middleware\TrustHostingProxy;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Adresse IP réelle et HTTPS derrière le proxy d'Alwaysdata (avant tout le reste).
        $middleware->prepend(TrustHostingProxy::class);

        $middleware->web(append: [
            SecurityHeaders::class,
            EnsureAccountIsActive::class,
            TouchLastSeen::class,
        ]);

        $middleware->alias([
            'staff.2fa' => EnsureStaffHasTwoFactor::class,
            'compte.actif' => EnsureAccountIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
