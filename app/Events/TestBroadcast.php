<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class TestBroadcast implements ShouldBroadcast
{
    public function broadcastOn()
    {
        return new Channel('members');
    }

    public function broadcastAs()
    {
        return 'TestEvent';
    }

    public function broadcastWith()
    {
        return ['message' => 'Testing broadcast'];
    }
}
