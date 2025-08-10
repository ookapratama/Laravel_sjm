<?php
// App\Events\UserNotificationReceived.php
namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class UserNotificationReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $notification;

    public function __construct($userId, array $notification)
    {
        $this->userId = $userId;
        $this->notification = $notification;
        \Log::info('🔥 Notifikasi siap dikirim ke user_id: ' . $userId, $notification);
    }


    public function broadcastOn()
    {
        return new PrivateChannel("private-notifications.{$this->userId}");
    }

    public function broadcastAs()
    {
        return 'notification.received';
    }
}
