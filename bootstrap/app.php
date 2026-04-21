<?php

use App\Exceptions\BusinessException;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\UserMiddleware;
use App\Support\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            Route::prefix('api/v1')
                ->as('api.')
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
        $exceptions->render(function (BusinessException $e) {
            return  new ApiResponse()->error(
                $e->getMessage(),
                $e->getCode(),
                $e->errors()
            );
        });
    })->create();
