<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
// Rate limiter moved to AppServiceProvider
use PHPOpenSourceSaver\JWTAuth\Http\Middleware\Authenticate as JWTAuthenticate;
use PHPOpenSourceSaver\JWTAuth\Http\Middleware\Authenticate as JWTAuthMiddleware;
use PHPOpenSourceSaver\JWTAuth\Http\Middleware\RefreshToken as JWTRefreshToken;
use PHPOpenSourceSaver\JWTAuth\Http\Middleware\Check as JWTCheck;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth.jwt' => JWTAuthMiddleware::class,
            'jwt.auth' => JWTAuthMiddleware::class,
            'jwt.refresh' => JWTRefreshToken::class,
            'jwt.check' => JWTCheck::class,
        ]);
    })
    // Rate limiter already registered above
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
