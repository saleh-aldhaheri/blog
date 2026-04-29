<?php

use App\Http\V1\Controllers\Api\Admin\AuthController;
use App\Http\V1\Controllers\Api\Admin\DashboardController;
use App\Http\V1\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;

// public routes
Route::post('/login', [AuthController::class, 'login'])
    ->withoutMiddleware(['auth:sanctum',  AdminMiddleware::class])
    ->name('login');

Route::get('/dashboard', DashboardController::class)->name('dashboard');

// private routes
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
