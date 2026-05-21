<?php

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
        // ✅ HARDENED: Apply idempotency middleware to all routes to prevent duplicate POST requests
        $middleware->append(\App\Http\Middleware\IdempotencyMiddleware::class);
        
        $middleware->alias([
            'checkRole'     => \App\Http\Middleware\CheckRole::class,
            'customerGuard' => \App\Http\Middleware\CustomerGuard::class,
            'idempotency'   => \App\Http\Middleware\IdempotencyMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
