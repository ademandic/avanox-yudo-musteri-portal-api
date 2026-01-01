<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'portal.api-key' => \App\Http\Middleware\ValidatePortalApiKey::class,
            'portal.log' => \App\Http\Middleware\LogApiRequests::class,
            'session.timeout' => \App\Http\Middleware\CheckSessionTimeout::class,
            'single.session' => \App\Http\Middleware\CheckSingleSession::class,
        ]);

        // API için login redirect yerine JSON 401 dön
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // API için authentication hatalarında JSON dön
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => 'Oturum süreniz doldu. Lütfen tekrar giriş yapın.',
            ], 401);
        });
    })->create();
