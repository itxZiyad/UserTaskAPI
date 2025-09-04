<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UploadController;
use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Support\Facades\Route;

Route::middleware([ForceJsonResponse::class])->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:api');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:api');

    Route::middleware(['auth:api', 'throttle:api'])->group(function () {
        Route::get('/tasks', [TaskController::class, 'index']);
        Route::post('/tasks', [TaskController::class, 'store']);
        Route::put('/tasks/{task}', [TaskController::class, 'update']);
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);

        Route::get('/uploads', [UploadController::class, 'index']);
        Route::post('/uploads', [UploadController::class, 'store']);
        Route::get('/uploads/{upload}', [UploadController::class, 'show']);
        Route::delete('/uploads/{upload}', [UploadController::class, 'destroy']);
    });
});


