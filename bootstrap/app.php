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
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'active_account' => \App\Http\Middleware\EnsureActiveAccount::class,
            'customer_auth' => \App\Http\Middleware\EnsureCustomerAuth::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\EnsureActiveAccount::class,
            \App\Http\Middleware\SyncReturnDeadlines::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();