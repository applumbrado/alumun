<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $e, $request) {

            // Si es petición de Inertia/Axios, forzar redirect duro al login
            if ($request->header('X-Inertia') || $request->expectsJson()) {
                return Inertia::location(route('login'));
            }

            // Si es navegación normal (form submit), redirigir al login
            return redirect()
                ->route('login')
                ->with('status', 'Tu sesión expiró. Inicia sesión de nuevo.');
        });
    })->create();
