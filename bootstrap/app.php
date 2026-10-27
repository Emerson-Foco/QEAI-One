<?php

use App\Http\Middleware\AuthenticateApiKey;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\EnsureMember;
use App\Http\Middleware\EnsureModuleEnabled;
use App\Http\Middleware\EnsureOrganizationAccess;
use App\Http\Middleware\EnsureRootAdmin;
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
            'installed' => EnsureInstalled::class,
            'root' => EnsureRootAdmin::class,
            'member' => EnsureMember::class,
            'org' => EnsureOrganizationAccess::class,
            'module' => EnsureModuleEnabled::class,
            'api.key' => AuthenticateApiKey::class,
        ]);

        // API pública (chave própria) e webhooks de entrada não usam CSRF.
        $middleware->validateCsrfTokens(except: ['api/*', 'hooks/*', 'webhooks/*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
