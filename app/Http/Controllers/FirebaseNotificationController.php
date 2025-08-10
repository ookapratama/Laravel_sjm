<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FirebaseNotificationController extends Controller
{
    /**
     * Kirim notifikasi ke satu FCM token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendNotification(Request $request)
    {
        $request->validate([
            'token' => 'required|string',           // Token FCM penerima
            'title' => 'required|string',           // Judul notifikasi
            'body' => 'required|string',            // Isi pesan
        ]);

        $fcmToken = $request->token;
        $serverKey = env('FCM_SERVER_KEY');

        $response = Http::withHeaders([
            'Authorization' => 'key=' . $serverKey,
            'Content-Type' => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', [
            'to' => $fcmToken,
            'notification' => [
                'title' => $request->title,
                'body' => $request->body,
                'sound' => 'default',
            ],
            'priority' => 'high',
            'data' => [
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK', // atau untuk JS: 'click_action' => 'OPEN_NOTIFICATION'
            ],
        ]);

        if ($response->successful()) {
            return response()->json(['message' => 'Notifikasi berhasil dikirim!']);
        } else {
            return response()->json([
                'message' => 'Gagal mengirim notifikasi',
                'response' => $response->body(),
            ], 500);
        }
    }
}
