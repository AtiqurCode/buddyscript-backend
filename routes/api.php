<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\PostController;
use Illuminate\Support\Facades\Route;

// Throttled separately from the rest of the API — these are the
// endpoints a credential-stuffing or fake-account script would hit.
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// throttle:120,1 keys by the authenticated user (auth:sanctum runs first and
// resolves them), not by IP — otherwise everyone behind the same NAT/proxy
// would share one bucket.
Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);

    Route::get('/posts', [PostController::class, 'index']);
    Route::post('/posts', [PostController::class, 'store']);
    Route::post('/posts/{post}/like', [PostController::class, 'like']);
    Route::get('/posts/{post}/likes', [PostController::class, 'likes']);

    Route::get('/posts/{post}/comments', [CommentController::class, 'index']);
    Route::post('/posts/{post}/comments', [CommentController::class, 'store']);
    Route::post('/comments/{comment}/replies', [CommentController::class, 'storeReply']);
    Route::post('/comments/{comment}/like', [CommentController::class, 'like']);
    Route::get('/comments/{comment}/likes', [CommentController::class, 'likes']);
});
