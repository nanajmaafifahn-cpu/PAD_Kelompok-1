<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // PAD-26: galat tak terduga tetap dilaporkan Laravel, tanpa detail debug di respons pengguna.
        $exceptions->respond(function ($response, $exception, Request $request) {
            $controller = $request->route()?->getControllerClass();

            if ($response->getStatusCode() < 500 || ! in_array($controller, [
                \App\Http\Controllers\RoomController::class,
                \App\Http\Controllers\BookingController::class,
                \App\Http\Controllers\ScheduleController::class,
                \App\Http\Controllers\UtilizationDashboardController::class,
            ], true)) {
                return $response;
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Terjadi kesalahan pada server. Coba lagi beberapa saat lagi.',
                ], $response->getStatusCode());
            }

            return response()->view('errors.500', [], $response->getStatusCode());
        });
    })->create();
