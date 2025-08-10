<?php


namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class SendAccountCredentialNotification extends Notification
{
    use Queueable;
    protected $username;
    protected $password;

    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }
use GuzzleHttp\Client;

protected function sendWhatsApp($phone, $message)
{
    // Format nomor ke +62 jika pakai 08
    if (str_starts_with($phone, '0')) {
        $phone = '+62' . substr($phone, 1);
    }

    try {
        $client = new Client();
        $client->post('https://api.fonnte.com/send', [
            'headers' => [
                'Authorization' => env('FONNTE_TOKEN'),
            ],
            'form_params' => [
                'target' => $phone,
                'message' => $message,
                'delay' => 2,
                'countryCode' => '62', // opsional
            ]
        ]);
    } catch (\Exception $e) {
        \Log::error("❌ Gagal kirim WhatsApp ke {$phone}: " . $e->getMessage());
    }
}


}