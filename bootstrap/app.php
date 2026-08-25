<?php

use App\Http\Middleware\CmsAuth;
use App\Http\Middleware\CrmAuth;
use App\Http\Middleware\ScheduleCmsCrmBackup;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // Registered outside the web group: an MCP client is not a browser, so
        // it carries no session and no CSRF token.
        then: function (): void {
            Route::prefix('mcp')
                ->middleware(['throttle:page-mcp'])
                ->group(__DIR__.'/../routes/mcp.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            ScheduleCmsCrmBackup::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'payments/razorpay/webhook',
        ]);

        $middleware->alias([
            'cms.auth' => CmsAuth::class,
            'crm.auth' => CrmAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
