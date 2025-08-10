<?php


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PreRegistration;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use App\Events\NewMemberApproved;
use App\Models\CashTransaction;
use App\Events\MemberCountUpdated;
use App\Events\NotificationReceived;
class PreRegistrationApprovalController extends Controller
{
public function approve($id)
{
    $pre = PreRegistration::findOrFail($id);

    if ($pre->status !== 'payment_verified') {
        return response()->json([
            'success' => false,
            'message' => 'Data ini sudah diproses sebelumnya.'
        ], 422);
    }

    $username = 'user' . Str::random(5);
    $password = Str::random(8);
    $sponsor = User::find($pre->sponsor_id);

    // ✅ Buat user & kaitkan dengan pre_registration_id
    $user = User::create([
        'name' => $pre->name,
        'email' => $pre->email,
        'phone' => $pre->phone,
        'username' => $username,
        'password' => Hash::make($password),
        'sponsor_id' => $sponsor->id ?? null,
        'pre_registration_id' => $pre->id, // 👉 penting
        'must_change_credentials' => true,
    ]);

    // ✅ Update pre_registrasi
    $pre->update([
        'status' => 'approved',
        'user_id' => $user->id // opsional, hanya jika ada kolom ini
    ]);

    // ✅ Event: Update dashboard admin
    event(new MemberCountUpdated(User::count()));

    // ✅ Catat kas
    if (! CashTransaction::where('source', 'registration')->where('user_id', $user->id)->exists()) {
        CashTransaction::create([
            'user_id' => $user->id,
            'type' => 'in',
            'source' => 'registration',
            'amount' => 1500000,
            'notes' => 'Dari pre-registration: ' . $pre->name,
            'payment_channel' => $pre->payment_method,
            'payment_reference' => $pre->payment_proof,
        ]);
    }

    // ✅ Broadcast ke upline
    event(new NewMemberApproved($user->sponsor_id, $user));

    // ✅ Kirim pesan WA
    $message = "Assalamu'alaikum {$user->name},\n\nAkun Anda di *PT. SAIR JAYA MANDIRI* telah dibuat:\n\n📌 Username: *{$username}*\n🔒 Password: *{$password}*\n\nSilakan login di https://sairjayamandiri.com/login dan segera ganti username dan password Anda.";
    $this->sendWhatsApp($pre->phone, $message);

    return response()->json([
        'success' => true,
        'message' => "Akun untuk {$user->name} berhasil dibuat, kas dicatat, dan WA telah dikirim."
    ]);
}


    // ✅ PASTIKAN fungsi ini ada DI DALAM class
    protected function sendWhatsApp($phone, $message)
    {
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
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error("❌ Gagal kirim WA ke {$phone}: " . $e->getMessage());
        }
    }


}
