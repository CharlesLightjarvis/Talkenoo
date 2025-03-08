<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\ConversationController;
use App\Http\Controllers\Api\v1\MessageController;
use App\Http\Controllers\Api\v1\UserController;

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    // Messaging Routes
    Route::prefix('messages')->group(function () {
        Route::get('conversations', [MessageController::class, 'getConversations']);
        Route::get('conversations/{userId}', [MessageController::class, 'getConversation']);
        Route::post('send/{conversationId}', [MessageController::class, 'sendMessage']);
        Route::get('{conversationId}', [MessageController::class, 'getMessages']);
        Route::post('/conversations/{conversationId}/typing', [MessageController::class, 'typing']);
    });
    // Route pour mettre à jour les statuts des messages à "delivered"
    Route::post('/messages/update-statuses', [ConversationController::class, 'updateMessageStatuses']);
    Route::get('conversations', [ConversationController::class, 'getOneOnOneConversations']);
    Route::get('conversations/{conversationId}', [ConversationController::class, 'getConversationMessages']);
});
