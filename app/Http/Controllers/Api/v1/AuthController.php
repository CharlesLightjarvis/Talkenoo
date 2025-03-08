<?php

namespace App\Http\Controllers\Api\v1;

use App\Enums\StatusEnum;
use App\Events\UserUpdateStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        // Update the user's status to "online"
        $user->status = StatusEnum::ONLINE->value;
        $user->last_active = now();
        $user->save();

        // Broadcast the status update
        broadcast(new UserUpdateStatus($user))->toOthers();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        // Update the user's status to "offline"
        $user->status = StatusEnum::OFFLINE->value;
        $user->last_active = now();

        $user->save();

        // Broadcast the status update
        broadcast(new UserUpdateStatus($user))->toOthers();

        // Delete the user's tokens (logout)
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
