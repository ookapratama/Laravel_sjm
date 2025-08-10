<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class BroadcastTest implements ShouldBroadcast
{
    public function broadcastOn()
    {
        return new Channel('members');
    }

    public function broadcastAs()
    {
        return 'BroadcastTest';
    }

    public function broadcastWith()
    {
        return ['status' => 'ok', 'time' => now()->toDateTimeString()];
    }
}

