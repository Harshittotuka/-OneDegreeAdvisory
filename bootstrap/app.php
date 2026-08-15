<?php

use App\Http\Middleware\CmsAuth;
use App\Http\Middleware\CrmAuth;
use App\Http\Middleware\ScheduleCmsCrmBackup;
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
