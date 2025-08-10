<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MemberCountUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $memberCount;

    public function __construct(int $memberCount)
    {
        $this->memberCount = $memberCount;
    }

    public function broadcastOn()
    {
        return new Channel('member-channel');
    }

    public function broadcastAs()
    {
        return 'member.updated';
    }
}
