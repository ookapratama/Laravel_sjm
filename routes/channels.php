<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/
// routes/channels.php

Broadcast::channel('notifications.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('private-notifications.{userId}', function ($user, $userId) {
    Log::info('Channel auth', [
        'channel' => "private-notifications.{$userId}",
        'user_id' => $user ? $user->id : 'null',
        'authorized' => $user && (int) $user->id === (int) $userId
    ]);
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    // PERBAIKAN DI SINI: Cek apakah $user ada
    return $user && (int) $user->id === (int) $id;
});

Broadcast::channel('upline.{userId}', function ($user, $userId) {
    // PERBAIKAN DI SINI: Cek apakah $user ada
    return $user && (int) $user->id === (int) $userId;
});
