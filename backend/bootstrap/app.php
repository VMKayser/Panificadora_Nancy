<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);

        // Cabeceras de seguridad en todas las respuestas (antes se registraban con
        // afterResolving(Router) en AppServiceProvider, que en Laravel 12 nunca se ejecutaba).
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Límite de tasa general para toda la API (limitador "api" en AppServiceProvider).
        $middleware->throttleApi();

        // API: devolver JSON 401 en vez de redirect
        $middleware->redirectGuestsTo(fn($request) =>
            $request->expectsJson() ? null : route('login', [], false)
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Errores inesperados en la API: respuesta genérica con un id para cruzarla
        // con el log. Validación, auth, 404, 429, etc. conservan su respuesta normal.
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (!$request->is('api/*') || config('app.debug')) {
                return null;
            }
            if ($e instanceof HttpExceptionInterface
                || $e instanceof \Illuminate\Validation\ValidationException
                || $e instanceof \Illuminate\Auth\AuthenticationException
                || $e instanceof \Illuminate\Auth\Access\AuthorizationException
                || $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
                || $e instanceof \Illuminate\Http\Exceptions\HttpResponseException) {
                return null;
            }

            // El detalle completo ya lo registró el reporter; aquí solo se enlaza con el id.
            $errorId = (string) Str::uuid();
            Log::error("Error no controlado [{$errorId}]", [
                'clase' => get_class($e),
                'ruta' => $request->path(),
            ]);

            return response()->json([
                'message' => 'Ocurrió un error interno. Intenta de nuevo más tarde.',
                'error_id' => $errorId,
            ], 500);
        });
    })->create();
