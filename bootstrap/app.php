<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ManageSiteContext;
use App\Http\Middleware\ShareUiSettings;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // Exclude legacy IoT device endpoints from CSRF verification
        // These endpoints are called by deployed gateways/meters without CSRF tokens
        $middleware->validateCsrfTokens(except: [
            '/http_post_server.php',
            '/rtu/index.php/*',
            '/check_time.php',
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            ManageSiteContext::class,
            HandleInertiaRequests::class,
            ShareUiSettings::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'admin' => AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
