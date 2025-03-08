<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Conversation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\MessageStatus;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ConversationController extends Controller
{

    /**
     * Helper method to check if a user is an instance of User Model
     */
    private function authUser()
    {
        $user = Auth::user();

        if (!$user instanceof User) {
            throw new \Exception('Invalid user');
        }

        return $user;
    }

    /**
     * Get all one-on-one conversations for the current user and show the last message in the sidebar
     */
    public function getOneOnOneConversations(Request $request)
    {
        $user = $this->authUser();

        $conversations = $user->conversations()
            ->where('is_group', false)
            ->with(['lastMessage', 'participants.user'])
            ->withCount(['messages as unread_count' => function ($query) use ($user) {
                $query->whereDoesntHave('messageStatus', function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                        ->whereIn('status', ['read', 'delivered']); // Vérifie si "delivered" est aussi utilisé
                });
            }])
            ->get()
            ->map(function ($conversation) use ($user) {
                // Get the other user in the conversation
                $otherUser = $conversation->participants()
                    ->with('user')
                    ->whereHas('user', function ($q) use ($user) {
                        $q->where('id', '!=', $user->id);
                    })
                    ->first();

                // Format the data for the frontend
                return [
                    'id' => $conversation->id,
                    'unread_count' => $conversation->unread_count,
                    'last_message' => $conversation->lastMessage ? [
                        'content' => $conversation->lastMessage->content,
                        'sent_at' => $conversation->lastMessage->sent_at->format('H:i'),
                        'is_own' => $conversation->lastMessage->user_id === $user->id,
                    ] : null,
                    'contact' => $otherUser ? [
                        'id' => $otherUser->user->id,
                        'fullName' => $otherUser->user->fullName,
                        'profile_picture' => $otherUser->user->profile_picture,
                        'status' => $otherUser->user->status,
                        'last_active' => $otherUser->user->last_active->format('Y-M-d H:i:s'),
                    ] : null
                ];
            });

        return response()->json(['conversations' => $conversations]);
    }

    /**
     * Get all group conversations for the current user
     */
    public function getGroupConversations(Request $request)
    {
        $user = $request->user();

        $conversations = $user->conversations()
            ->where('is_group', true)
            ->with(['lastMessage', 'participants.user'])
            ->withCount(['messages as unread_count' => function ($query) use ($user) {
                $query->whereDoesntHave('messageStatus', function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                        ->where('status', 'read');
                });
            }])
            ->get()
            ->map(function ($conversation) use ($user) {
                // Get basic group info
                $result = [
                    'id' => $conversation->id,
                    'name' => $conversation->name,
                    'unread_count' => $conversation->unread_count,
                    'last_message' => $conversation->lastMessage ? [
                        'content' => $conversation->lastMessage->content,
                        'sender_name' => $conversation->lastMessage->user->fullName,
                        'sent_at' => $conversation->lastMessage->sent_at,
                        'is_own' => $conversation->lastMessage->user_id === $user->id,
                    ] : null,
                    'member_count' => $conversation->participants()->whereNull('left_at')->count()
                ];

                return $result;
            });

        return response()->json(['conversations' => $conversations]);
    }

    /**
     * Get messages for a specific conversation
     */
    public function getConversationMessages($conversationId)
    {
        $user = $this->authUser();

        // Vérifier si l'utilisateur fait partie de la conversation
        $conversation = $user->conversations()
            ->where('conversations.id', $conversationId)
            ->firstOrFail();

        // Récupérer les messages paginés
        $perPage = request('per_page', 20); // Nombre de messages par page (par défaut 20)
        $messages = $conversation->messages()
            ->with(['user', 'messageStatus'])
            ->orderBy('sent_at', 'desc') // Charger du plus récent au plus ancien
            ->paginate($perPage);

        // Mapper les messages pour le format frontend
        $formattedMessages = $messages->map(function ($message) use ($user) {
            $status = null;

            if ($message->user_id === $user->id) {
                $status = 'sent'; // Message envoyé par l'utilisateur
            } else {
                $messageStatus = $message->messageStatus->where('user_id', $user->id)->first();
                $status = $messageStatus ? $messageStatus->status : 'delivered';
            }

            return [
                'id' => $message->id,
                'content' => $message->content,
                'sent_at' => $message->sent_at->format('Y-m-d H:i:s'),
                'is_edited' => $message->is_edited,
                'is_deleted' => $message->is_deleted,
                'user' => [
                    'id' => $message->user->id,
                    'fullName' => $message->user->fullName,
                    'profile_picture' => $message->user->profile_picture,
                ],
                'is_own' => $message->user_id === $user->id,
                'status' => $status,
            ];
        });

        // Marquer les messages comme lus
        $this->markMessagesAsRead($conversationId, $user->id);

        // Récupérer les détails de la conversation
        $details = [
            'id' => $conversation->id,
            'is_group' => $conversation->is_group,
            'messages' => $formattedMessages,
            'pagination' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ]
        ];

        // Ajouter les infos du groupe ou du contact
        if ($conversation->is_group) {
            $details['name'] = $conversation->name;
            $details['members'] = $conversation->participants()
                ->with('user')
                ->whereNull('left_at')
                ->get()
                ->map(function ($participant) {
                    return [
                        'id' => $participant->user->id,
                        'fullName' => $participant->user->fullName,
                        'profile_picture' => $participant->user->profile_picture,
                        'is_owner' => $participant->is_owner,
                    ];
                });
        } else {
            $otherUser = $conversation->participants()
                ->with('user')
                ->whereHas('user', function ($q) use ($user) {
                    $q->where('id', '!=', $user->id);
                })
                ->first();

            $details['contact'] = $otherUser ? [
                'id' => $otherUser->user->id,
                'name' => $otherUser->user->fullName,
                'profile_picture' => $otherUser->user->profile_picture,
                'status' => $otherUser->user->status,
                'last_active' => $otherUser->user->last_active,
            ] : null;
        }

        return response()->json(['conversation' => $details]);
    }


    /**
     * Mark all unread messages in a conversation as read
     */
    private function markMessagesAsRead($conversationId, $userId)
    {
        // Get messages that aren't from the current user
        $messages = Message::where('conversation_id', $conversationId)
            ->where('user_id', '!=', $userId)
            ->get();

        foreach ($messages as $message) {
            // Check current status
            $currentStatus = MessageStatus::where('message_id', $message->id)
                ->where('user_id', $userId)
                ->first();

            if (!$currentStatus) {
                // If no status record exists, create one with 'read' status
                MessageStatus::create([
                    'message_id' => $message->id,
                    'user_id' => $userId,
                    'status' => 'read',
                    'delivered_at' => now(),
                    'read_at' => now()
                ]);
            } elseif ($currentStatus->status !== 'read') {
                // If status exists but isn't 'read', update it
                $currentStatus->status = 'read';
                $currentStatus->read_at = now();

                // Set delivered_at if not already set
                if (!$currentStatus->delivered_at) {
                    $currentStatus->delivered_at = now();
                }

                $currentStatus->save();
            }
        }
    }


    /**
     * Update message status to 'delivered' when messages are loaded but not yet read
     * Useful for when a user enters the app but hasn't opened a specific conversation yet
     */
    public function updateMessageStatuses(Request $request)
    {
        $user = $this->authUser();
        $conversationIds = $user->conversations()->pluck('conversations.id');

        // Find messages without a 'delivered' or 'read' status for this user
        $messages = Message::whereIn('conversation_id', $conversationIds)
            ->where('user_id', '!=', $user->id)
            ->whereDoesntHave('messageStatus', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->whereIn('status', ['delivered', 'read']);
            })
            ->get();

        foreach ($messages as $message) {
            // Check if we have a status record
            $status = MessageStatus::where('message_id', $message->id)
                ->where('user_id', $user->id)
                ->first();

            if (!$status) {
                // Create new status record
                MessageStatus::create([
                    'message_id' => $message->id,
                    'user_id' => $user->id,
                    'status' => 'delivered',
                    'delivered_at' => now()
                ]);
            } elseif ($status->status === 'sent') {
                // Update existing status from 'sent' to 'delivered'
                $status->status = 'delivered';
                $status->delivered_at = now();
                $status->save();
            }
        }

        return response()->json(['message' => 'Message statuses updated']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function createOneOnOne(Request $request)
    {
        $request->validate([
            'participant_id' => 'required|exists:users,id'
        ]);

        // Don't allow creating conversation with self
        if ($request->participant_id == Auth::id()) {
            return response()->json(['message' => 'Cannot create conversation with yourself'], 400);
        }

        // Check if a one-on-one conversation already exists between these users
        $existingConversation = Conversation::whereHas('participants', function ($query) {
            $query->where('user_id', Auth::id());
        })
            ->whereHas('participants', function ($query) use ($request) {
                $query->where('user_id', $request->participant_id);
            })
            ->where('is_group', false)
            ->first();

        if ($existingConversation) {
            return response()->json($existingConversation->load(['participants.user', 'lastMessage']));
        }

        // Create new conversation
        $conversation = new Conversation();
        // $conversation->id = Str::uuid();
        $conversation->is_group = false;
        $conversation->save();

        // Add both users as participants
        $conversation->participants()->createMany([
            ['user_id' => Auth::id()],
            ['user_id' => $request->participant_id]
        ]);

        return response()->json($conversation->load(['participants.user']));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'participants' => 'required|array|min:1',
            'participants.*' => 'exists:users,id'
        ]);

        $conversation = new Conversation();
        // $conversation->id = Str::uuid();
        $conversation->title = $request->title;
        $conversation->created_by = Auth::id();
        $conversation->is_group = true;
        $conversation->save();

        // Add participants including the creator
        $participants = collect($request->participants)
            ->unique()
            ->push(Auth::id())
            ->map(function ($userId) {
                return ['user_id' => $userId];
            });
        $conversation->participants()->createMany($participants);

        return response()->json($conversation->load(['participants.user']));
    }

    /**
     * Display the specified resource.
     */
    public function show(Conversation $conversation)
    {
        // $this->authorize('view', $conversation);

        return response()->json($conversation->load(['participants.user', 'messages.user']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Conversation $conversation)
    {
        // $this->authorize('update', $conversation);

        $request->validate([
            'title' => 'required|string|max:255'
        ]);

        $conversation->title = $request->title;
        $conversation->save();

        return response()->json($conversation->load('participants.user'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Conversation $conversation)
    {
        // $this->authorize('delete', $conversation);

        $conversation->participants()->delete();
        $conversation->delete();

        return response()->json(['message' => 'Conversation deleted successfully']);
    }

    /**
     * Add participants to the conversation.
     */
    public function addParticipants(Request $request, Conversation $conversation)
    {
        // $this->authorize('update', $conversation);

        $request->validate([
            'participants' => 'required|array|min:1',
            'participants.*' => 'exists:users,id'
        ]);

        $existingParticipants = $conversation->participants()->pluck('user_id');
        $newParticipants = collect($request->participants)
            ->diff($existingParticipants)
            ->map(function ($userId) {
                return ['user_id' => $userId];
            });

        if ($newParticipants->isNotEmpty()) {
            $conversation->participants()->createMany($newParticipants);
        }

        return response()->json($conversation->load('participants.user'));
    }

    /**
     * Remove a participant from the conversation.
     */
    public function removeParticipant(Request $request, Conversation $conversation)
    {
        // $this->authorize('update', $conversation);

        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        if ($request->user_id != $conversation->created_by) {
            $conversation->participants()
                ->where('user_id', $request->user_id)
                ->delete();

            return response()->json(['message' => 'Participant removed successfully']);
        }

        return response()->json(['message' => 'Cannot remove the conversation creator'], 403);
    }
}
