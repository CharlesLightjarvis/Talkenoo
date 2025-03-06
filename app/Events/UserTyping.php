<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversationId;
    public $isTyping;
    public $user;

    public function __construct(User $user, $conversationId, $isTyping)
    {
        $this->user = $user;  // The user who is typing.
        $this->conversationId = $conversationId;
        $this->isTyping = $isTyping;
    }

    public function broadcastOn()
    {
        return new PresenceChannel('typing.' . $this->conversationId);
    }

    public function broadcastWith()
    {
        return [
            'is_typing' => $this->isTyping,
            'user' => [
                'id' => $this->user->id,  // Send only needed fields
                'fullName' => $this->user->fullName,
            ],
        ];
    }
}
