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
            'tenant' => \App\Http\Middleware\SetTenantContext::class,
            'subscription.active' => \App\Http\Middleware\EnsureSubscriptionActive::class,
            'subscription.gate' => \App\Http\Middleware\FeatureGate::class,
            'super.admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
        ]);

        $middleware->appendToGroup('web', [
            \App\Http\Middleware\SetTenantContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
