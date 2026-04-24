<?php

use App\Http\Controllers\Api\V1\User\AuthController;
use App\Http\Controllers\Api\V1\User\CommentController;
use App\Http\Controllers\Api\V1\User\CommentInteractionController;
use App\Http\Controllers\Api\V1\User\FollowController;
use App\Http\Controllers\Api\V1\User\PostController;
use App\Http\Controllers\Api\V1\User\PostInteractionController;
use App\Http\Middleware\UserMiddleware;
use Illuminate\Support\Facades\Route;

// public routes
Route::withoutMiddleware(['auth:sanctum', UserMiddleware::class])->group(function () {
    Route::post('/login', [AuthController::class,  'login'])->name('login');
    Route::Post('/register', [AuthController::class,  'register'])->name('register');
});

// private routes
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/users/{user}/posts', [PostController::class, 'userPosts'])->name('user.posts');

Route::get('/posts/viewed', [PostController::class, 'viewedPosts'])->name('posts.viewed');
Route::apiResource('/posts', PostController::class)
    ->middlewareFor('update', 'can:update,post')
    ->middlewareFor('show', 'can:view,post')
    ->middlewareFor('destroy', 'can:delete,post');

Route::apiResource('posts.comments', CommentController::class)
    ->shallow()
    ->except('show')
    ->scoped()
    ->middlewareFor('update', 'can:update,comment')
    ->middlewareFor('destroy', 'can:delete,comment');

Route::apiResource('posts.interactions', PostInteractionController::class)
    ->middlewareFor('destroy', 'can:delete,interaction')
    ->scoped()
    ->except('index', 'show', 'update');

Route::apiResource('comments.interactions', CommentInteractionController::class)
    ->middlewareFor('destroy', 'can:delete,interaction')
    ->scoped()
    ->except('index', 'show', 'update');

Route::prefix('follow')->as('follow.')->group(function () {
    Route::get('/following', [FollowController::class,  'followings'])->name('following');
    Route::get('/followers', [FollowController::class,  'followers'])->name('followers');

    Route::put('/following/follow/{user}', [FollowController::class,  'follow'])->name('follow');
    Route::put('/following/unfollow/{user}', [FollowController::class,  'unfollow'])->name('unfollow');
});
