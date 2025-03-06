<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\MessageController;

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    // Messaging Routes
    Route::prefix('messages')->group(function () {
        Route::get('conversations', [MessageController::class, 'getConversations']);
        Route::get('conversations/{userId}', [MessageController::class, 'getConversation']);
        Route::post('send/{conversationId}', [MessageController::class, 'sendMessage']);
        Route::get('{conversationId}', [MessageController::class, 'getMessages']);
    });
});
