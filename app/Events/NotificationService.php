<?php

namespace App\Events;

use App\Models\Notification;

class NotificationService
{
  public static function sendNotification($userId, $type, $message, $url = null, $additionalData = [])
  {
    try {
      // Create notification
      $notification = Notification::create([
        'user_id' => $userId,
        'type' => $type,
        'message' => $message,
        'url' => $url,
        'is_read' => false,
        'data' => json_encode($additionalData)
      ]);

      // Prepare broadcast data
      $broadcastData = [
        'id' => $notification->id,
        'type' => $type,
        'message' => $message,
        'url' => $url,
        'created_at' => now()->toDateTimeString(),
        'user_id' => $userId
      ];

      // Choose appropriate event based on type
      switch ($type) {
        case 'rejected_pin':
          event(new PinRequestRejected($userId, $broadcastData));
          break;
        // Add more cases as needed
        default:
          event(new NotificationReceived($userId, $broadcastData));
          break;
      }

      \Log::info('Notification sent successfully', [
        'notification_id' => $notification->id,
        'user_id' => $userId,
        'type' => $type
      ]);

      return $notification;
    } catch (\Exception $e) {
      \Log::error('Failed to send notification', [
        'user_id' => $userId,
        'type' => $type,
        'error' => $e->getMessage()
      ]);

      throw $e;
    }
  }
}
