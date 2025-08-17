<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;


class MemberPinRequest implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notification;
    public $userId;

    public function __construct($userId, $notification)
    {
        $this->notification = $notification;
        $this->userId = $userId;
    }

    public function broadcastOn()
    {
        
        // ✅ FIX: Sesuaikan dengan channel yang JavaScript listen
        return new PrivateChannel("notifications.{$this->userId}");
    }

    public function broadcastAs()
    {
        // ✅ FIX: Sesuaikan dengan event yang JavaScript listen
        return 'notification.received';
    }

    public function broadcastWith()
    {
        return  [
            'notification' => $this->notification
        ];
    }
}
