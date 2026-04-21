<?php

use App\Exceptions\BusinessException;
use App\Http\V1\Middleware\AdminMiddleware;
use App\Http\V1\Middleware\UserMiddleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::prefix('api/v1')
                ->as('api.')
                ->middleware([SubstituteBindings::class])
                ->group(function () {
                    Route::prefix('admin')
                        ->middleware(['auth:sanctum', AdminMiddleware::class])
                        ->as('admin.')
                        ->group(base_path('routes/api/v1/admin.php'));

                    Route::as('user.')
                        ->middleware(['auth:sanctum', UserMiddleware::class])
                        ->group(base_path('routes/api/v1/user.php'));
                });
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], $e->status);
            }

            if ($e instanceof BusinessException) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], $e->getCode());
            }

            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 401);
            }

            if ($e instanceof AccessDeniedHttpException) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'This action is unauthorized.',
                ], 403);
            }

            if ($e instanceof NotFoundHttpException) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'Resource not found.',
                ], 404);
            }

            return response()->json([
                'message' => 'Server error',
            ], 500);
        });
    })->create();
