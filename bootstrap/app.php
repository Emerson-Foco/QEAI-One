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
            'installed' => \App\Http\Middleware\EnsureInstalled::class,
            'root' => \App\Http\Middleware\EnsureRootAdmin::class,
            'member' => \App\Http\Middleware\EnsureMember::class,
            'org' => \App\Http\Middleware\EnsureOrganizationAccess::class,
            'api.key' => \App\Http\Middleware\AuthenticateApiKey::class,
        ]);

        // A API pública usa chave própria, sem CSRF.
        $middleware->validateCsrfTokens(except: ['api/*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
