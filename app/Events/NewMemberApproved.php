<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Events\NewMemberApproved;

class NewMemberApproved implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $sponsorId;
    public $user;

    public function __construct($sponsorId, User $user)
    {
        $this->sponsorId = $sponsorId;
        $this->user = $user;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('upline.' . $this->sponsorId);
    }

    public function broadcastAs()
    {
        return 'NewMemberApproved';
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'username' => $this->user->username,
            'created_at' => $this->user->created_at->toDateTimeString(),
        ];
    }
}

