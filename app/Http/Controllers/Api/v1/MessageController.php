<?php

namespace App\Http\Controllers\Api\v1;

use App\Events\MessageSent;
use App\Events\UserTyping;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\MessageStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function sendMessage(Request $request, $conversationId)
    {
        $request->validate([
            'content' => 'required|string'
        ]);

        $conversation = Conversation::findOrFail($conversationId);

        // Check if user is part of the conversation
        if (!$conversation->users()->where('user_id', Auth::id())->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $message = Message::create([
            'conversation_id' => $conversationId,
            'user_id' => Auth::id(),
            'content' => $request->content
        ]);

        // Load the user relationship
        $message->load('user');

        // Create message status for all participants except sender
        $conversation->users()
            ->where('user_id', '!=', Auth::id())
            ->get()
            ->each(function ($user) use ($message) {
                MessageStatus::create([
                    'message_id' => $message->id,
                    'user_id' => $user->id,
                    'status' => 'sent'
                ]);
            });

        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message);
    }

    public function getMessages(Request $request, $conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Check if user is part of the conversation
        if (!$conversation->users()->where('user_id', Auth::id())->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $messages = $conversation->messages()
            ->with(['user', 'messageStatus'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Update message status to 'read' for all unread messages
        $messages->each(function ($message) {
            if ($message->user_id !== Auth::id()) {
                MessageStatus::updateOrCreate(
                    [
                        'message_id' => $message->id,
                        'user_id' => Auth::id()
                    ],
                    ['status' => 'read']
                );
            }
        });

        return response()->json($messages);
    }

    public function typing(Request $request, $conversationId)
    {
        $request->validate([
            'is_typing' => 'required|boolean',
        ]);

        $user = Auth::user();
        if (!$user instanceof User) {
            return response()->json(['error' => 'User not found'], 404);
        }

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Diffuser l'événement de saisie en excluant l'utilisateur actuel
        broadcast(new UserTyping($user, $conversationId, $request->is_typing))->toOthers();

        return response()->json(['status' => 'success']);
    }
}
