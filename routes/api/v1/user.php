<?php

use App\Http\V1\Controllers\Api\User\AuthController;
use App\Http\V1\Controllers\Api\User\CategoryController;
use App\Http\V1\Controllers\Api\User\CommentController;
use App\Http\V1\Controllers\Api\User\CommentInteractionController;
use App\Http\V1\Controllers\Api\User\FollowController;
use App\Http\V1\Controllers\Api\User\PostController;
use App\Http\V1\Controllers\Api\User\PostInteractionController;
use App\Http\V1\Controllers\Api\User\ProfileController;
use App\Http\V1\Middleware\UserMiddleware;
use Illuminate\Support\Facades\Route;

// public routes
Route::withoutMiddleware(['auth:sanctum', UserMiddleware::class])->group(function () {
    Route::post('/login', [AuthController::class,  'login'])->name('login');
    Route::Post('/register', [AuthController::class,  'register'])->name('register');
});

// private routes
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

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

Route::get('/categories', [CategoryController::class, 'index'])->name('categories');
