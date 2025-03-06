<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\MessageStatus;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageStatusController extends Controller
{
    /**
     * Update message status for a user
     */
    public function update(Request $request, Message $message)
    {
        $request->validate([
            'status' => 'required|in:delivered,read'
        ]);

        $status = MessageStatus::where('message_id', $message->id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $status->status = $request->status;
        if ($request->status === 'read' && !$status->read_at) {
            $status->read_at = now();
        }
        $status->save();

        return response()->json($status);
    }

    /**
     * Mark all messages in a conversation as read for the authenticated user
     */
    public function markAllRead(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id'
        ]);

        $conversation = Conversation::findOrFail($request->conversation_id);
        $this->authorize('view', $conversation);

        $messageIds = Message::where('conversation_id', $request->conversation_id)
            ->pluck('id');

        MessageStatus::whereIn('message_id', $messageIds)
            ->where('user_id', Auth::id())
            ->where('status', '!=', 'read')
            ->update([
                'status' => 'read',
                'read_at' => now()
            ]);

        return response()->json(['message' => 'All messages marked as read']);
    }

    /**
     * Get unread message count for the authenticated user
     */
    public function getUnreadCount(Request $request)
    {
        $request->validate([
            'conversation_id' => 'nullable|exists:conversations,id'
        ]);

        $query = MessageStatus::where('user_id', Auth::id())
            ->where('status', '!=', 'read');

        if ($request->has('conversation_id')) {
            $messageIds = Message::where('conversation_id', $request->conversation_id)
                ->pluck('id');
            $query->whereIn('message_id', $messageIds);
        }

        $unreadCount = $query->count();

        return response()->json([
            'unread_count' => $unreadCount
        ]);
    }
}
