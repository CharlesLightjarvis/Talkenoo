<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class UserUpdateStatus implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function broadcastOn()
    {
        return new PresenceChannel('user-status');
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->user->id,
            'fullName' => $this->user->fullName,
            'status' => $this->user->status,
            'last_active' => $this->user->last_active,
        ];
    }
}
