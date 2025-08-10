<?php
namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Withdrawal;
use App\Models\CashTransaction;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class WithdrawController extends Controller
{
    public function index()
    {
        $withdraws = Withdrawal::where('status', 'menunggu')->latest()->get();
        return view('finance.index', compact('withdraws'));
    }

public function process($id, Request $request)
{
   try {
        $request->validate([
            'transfer_reference' => 'required|string|max:100',
        ]);

        // Ambil withdraw + user
        $withdraw = Withdrawal::with('user')->findOrFail($id);
        $user = $withdraw->user;

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        // Update status
        $withdraw->status = 'approved';
        $withdraw->transfer_reference = $request->transfer_reference;
        $withdraw->processed_at = now();
        $withdraw->save();

        // Catat ke cash_transactions
        CashTransaction::create([
            'user_id'           => $user->id,
            'type'              => 'out',
            'source'            => 'withdraw',
            'amount'            => $withdraw->amount,
            'notes'             => 'Withdraw user ' . $user->name,
            'payment_channel'   => 'Transfer',
            'payment_reference' => 'WD-' . $withdraw->id,
        ]);


            // Siapkan pesan
            $finance = auth()->user();
            Log::Error("Nomor HP User: " . $user->no_hp);
            $amount = number_format($withdraw->amount, 0, ',', '.');

            $message = "Assalamu'alaikum {$user->name},\n\n" .
                "✅ Penarikan dana Anda sebesar *Rp{$amount}* dari akun *PT. SAIR JAYA MANDIRI* telah berhasil diproses dan ditransfer ke rekening tujuan Anda.\n\n" .
                "💬 *Catatan dari Tim Finance:* Dana telah kami transfer sesuai data yang Anda berikan. Harap periksa akun Anda.\n\n" .
                "📌 Diproses oleh: *{$finance->name}* (Finance)\n\n" .
                "💡 *Motivasi hari ini:*\n" .
                "\"Jangan menyerah pada awal yang sulit. Semua hal besar butuh proses dan kesabaran.\"\n\n" .
                "Terima kasih telah menjadi bagian dari keluarga besar *Sair Jaya Mandiri*. Semoga rezeki Anda terus mengalir dan membawa keberkahan.";

            // Kirim WA
            $this->sendWhatsApp($user->no_telp, $message);
  

        return response()->json([
            'success' => true,
            'message' => 'Withdraw berhasil diproses oleh Finance.',
        ]);
    } catch (\Exception $e) {
        Log::error("❌ Error withdraw process: " . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Gagal memproses withdraw.',
        ], 500);
    }
}

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
