<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('fullName', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by online status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $users = $query->paginate(20);
        return response()->json($users);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return response()->json($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'fullName' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'sometimes|string|max:20',
            'profile_picture' => 'sometimes|string|max:255',
            'status' => 'sometimes|string|max:255',
            'password' => 'sometimes|string|min:8'
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json($user);
    }

    /**
     * Update user's online status
     */
    public function updateOnlineStatus(Request $request)
    {
        $user = Auth::user();
        $user->is_active = $request->boolean('is_active');
        $user->last_active = now();
        $user->save();

        return response()->json(['message' => 'Status updated successfully']);
    }

    /**
     * Get the authenticated user's profile
     */
    public function profile()
    {
        $user = Auth::user();
        return response()->json($user);
    }

    /**
     * Get user's conversations
     */
    public function conversations(User $user)
    {
        $this->authorize('viewConversations', $user);

        $conversations = $user->conversations()
            ->with(['lastMessage', 'participants'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return response()->json($conversations);
    }
}
