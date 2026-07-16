<?php

use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'jwt.auth' => AuthenticateJwt::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*'));
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $validationErrors = collect($exception->errors())
                ->flatMap(fn (array $messages, string $field) => collect($messages)->map(fn (string $message) => [
                    'field' => $field,
                    'code' => $message,
                ]))->values();

            return response()->json([
                'httpCode' => 400,
                'code' => 'VALIDATION_INVALID_ENTITY',
                'message' => 'VALIDATION_INVALID_ENTITY',
                'errors' => $validationErrors->pluck('code'),
                'validationErrors' => $validationErrors,
            ], 400);
        });
    })->create();
