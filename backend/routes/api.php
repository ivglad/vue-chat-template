<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\DocumentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1')->group(function () {
    // Авторизация
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/auth/user', [AuthController::class, 'user'])->middleware('auth:sanctum');

    // Защищенные маршруты
    Route::middleware('auth:sanctum')->group(function () {
        // Чат
        Route::prefix('chat')->group(function () {
            Route::get('/history', [ChatController::class, 'history']);
            Route::post('/send', [ChatController::class, 'send']);
            Route::delete('/clear', [ChatController::class, 'clear']);
        });

        // Документы
        Route::prefix('documents')->group(function () {
            Route::get('/', [DocumentController::class, 'index']);
            Route::get('/{id}', [DocumentController::class, 'show']);
        });
    });
}); 