<?php

use App\Http\Middleware\EnsureApplicationInstalled;
use App\Http\Middleware\EnsureAdminAuthenticated;
use App\Http\Middleware\RedirectIfAdminAuthenticated;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [
            EnsureApplicationInstalled::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'payments/alipay/notify',
            'payments/wechat/notify',
        ]);

        $middleware->alias([
            'admin.auth' => EnsureAdminAuthenticated::class,
            'admin.guest' => RedirectIfAdminAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
