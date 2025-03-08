<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.conversation.{conversationId}', function (User $user, $conversationId) {
    return $user && $user->conversations()->where('conversations.id', $conversationId)->exists();
});


Broadcast::channel('typing.{conversationId}', function (User $user, $conversationId) {
    return $user && $user->conversations()->where('conversations.id', $conversationId)->exists();
});

Broadcast::channel('user-status', function ($user) {
    return $user;
});
