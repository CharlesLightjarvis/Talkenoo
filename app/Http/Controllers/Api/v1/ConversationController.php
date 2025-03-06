<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Conversation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ConversationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $conversations = Conversation::whereHas('participants', function($query) {
            $query->where('user_id', Auth::id());
        })
        ->with(['participants.user', 'lastMessage'])
        ->orderBy('updated_at', 'desc')
        ->paginate(20);

        return response()->json($conversations);
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
        $existingConversation = Conversation::whereHas('participants', function($query) {
            $query->where('user_id', Auth::id());
        })
        ->whereHas('participants', function($query) use ($request) {
            $query->where('user_id', $request->participant_id);
        })
        ->where('is_group', false)
        ->first();

        if ($existingConversation) {
            return response()->json($existingConversation->load(['participants.user', 'lastMessage']));
        }

        // Create new conversation
        $conversation = new Conversation();
        $conversation->id = Str::uuid();
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
        $conversation->id = Str::uuid();
        $conversation->title = $request->title;
        $conversation->created_by = Auth::id();
        $conversation->is_group = true;
        $conversation->save();

        // Add participants including the creator
        $participants = collect($request->participants)
            ->unique()
            ->push(Auth::id())
            ->map(function($userId) {
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
        $this->authorize('view', $conversation);
        
        return response()->json($conversation->load(['participants.user', 'messages.user']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Conversation $conversation)
    {
        $this->authorize('update', $conversation);

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
        $this->authorize('delete', $conversation);
        
        $conversation->participants()->delete();
        $conversation->delete();
        
        return response()->json(['message' => 'Conversation deleted successfully']);
    }

    /**
     * Add participants to the conversation.
     */
    public function addParticipants(Request $request, Conversation $conversation)
    {
        $this->authorize('update', $conversation);

        $request->validate([
            'participants' => 'required|array|min:1',
            'participants.*' => 'exists:users,id'
        ]);

        $existingParticipants = $conversation->participants()->pluck('user_id');
        $newParticipants = collect($request->participants)
            ->diff($existingParticipants)
            ->map(function($userId) {
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
        $this->authorize('update', $conversation);

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
