<?php

use App\Http\Controllers\Api\V1\User\AuthController;
use App\Http\Middleware\UserMiddleware;
use Illuminate\Support\Facades\Route;

//public routes
Route::withoutMiddleware(['auth:sanctum', UserMiddleware::class])->group(function () {
    Route::post('/login', [AuthController::class,  'login'])->name('login');
    Route::Post('/register',  [AuthController::class,  'register'])->name('register');
});


//private routes
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
