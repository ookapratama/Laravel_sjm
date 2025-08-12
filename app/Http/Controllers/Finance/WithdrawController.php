<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Withdrawal;
use App\Models\CashTransaction;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WithdrawController extends Controller
{
    public function index()
    {
        // $withdraws = Withdrawal::where('status', 'menunggu')->latest()->get();
        $withdraws = Withdrawal::where('status', 'pending')->latest()->get();
        return view('finance.index', compact('withdraws'));
    }

    public function process($id, Request $request)
    {
        try {
            // Validasi berdasarkan action
            $rules = [
                'action' => 'required|in:approve,reject',
            ];

            if ($request->action === 'approve') {
                $rules['transfer_reference'] = 'required|string|max:100';
                $rules['admin_notes'] = 'nullable|string|max:500';
            } else {
                $rules['admin_notes'] = 'required|string|min:10|max:500';
            }

            $request->validate($rules);

            // Ambil withdraw + user
            $withdraw = Withdrawal::with('user')->findOrFail($id);
            $user = $withdraw->user;

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak ditemukan.',
                ], 404);
            }

            // Cek status withdrawal
            if ($withdraw->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Withdrawal sudah diproses sebelumnya.',
                ], 422);
            }

            $finance = auth()->user();
            $amount = number_format($withdraw->amount, 0, ',', '.');

            if ($request->action === 'approve') {
                // ==========================================
                // PROSES APPROVE
                // ==========================================

                // Update status withdrawal
                $withdraw->status = 'approved';
                $withdraw->transfer_reference = $request->transfer_reference;
                $withdraw->admin_notes = $request->admin_notes;
                // $withdraw->processed_at = now();
                // $withdraw->processed_by = auth()->id();
                $withdraw->save();

                // Catat ke cash_transactions
                CashTransaction::create([
                    'user_id'           => $user->id,
                    'type'              => 'out',
                    'source'            => 'withdraw',
                    'amount'            => $withdraw->amount,
                    'notes'             => 'Withdraw user ' . $user->name . ' - Approved by ' . $finance->name,
                    'payment_channel'   => 'Transfer',
                    'payment_reference' => 'WD-' . $withdraw->id,
                    'reference_no'      => $request->transfer_reference,
                ]);

                // Siapkan pesan WhatsApp untuk approval
                $message = "Assalamu'alaikum {$user->name},\n\n" .
                    "✅ *PENARIKAN DANA BERHASIL DIPROSES*\n\n" .
                    "Penarikan dana Anda sebesar *Rp {$amount}* dari akun *PT. SAIR JAYA MANDIRI* telah berhasil diproses dan ditransfer ke rekening tujuan Anda.\n\n" .
                    "📋 *Detail Transfer:*\n" .
                    "• Jumlah: Rp {$amount}\n" .
                    "• Referensi: {$request->transfer_reference}\n" .
                    "• Waktu Proses: " . now()->format('d/m/Y H:i') . "\n\n";

                if ($request->admin_notes) {
                    $message .= "💬 *Catatan dari Tim Finance:*\n{$request->admin_notes}\n\n";
                } else {
                    $message .= "💬 *Catatan dari Tim Finance:* Dana telah kami transfer sesuai data yang Anda berikan. Harap periksa akun Anda.\n\n";
                }

                $message .= "📌 Diproses oleh: *{$finance->name}* (Finance)\n\n" .
                    "💡 *Motivasi hari ini:*\n" .
                    "\"Jangan menyerah pada awal yang sulit. Semua hal besar butuh proses dan kesabaran.\"\n\n" .
                    "Terima kasih telah menjadi bagian dari keluarga besar *Sair Jaya Mandiri*. Semoga rezeki Anda terus mengalir dan membawa keberkahan.";

                $responseMessage = 'Withdrawal berhasil disetujui dan diproses.';
            } else {
                // ==========================================
                // PROSES REJECT
                // ==========================================

                // Update status withdrawal
                $withdraw->status = 'rejected';
                $withdraw->admin_notes = $request->admin_notes;
                // $withdraw->processed_at = now();
                // $withdraw->processed_by = auth()->id();
                $withdraw->save();

                // Catat ke cash_transactions sebagai reversal
                // CashTransaction::create([
                //     'user_id'           => $user->id,
                //     'type'              => 'reversal',
                //     'source'            => 'withdraw_rejected',
                //     'amount'            => $withdraw->amount,
                //     'notes'             => 'Withdraw user ' . $user->name . ' - Rejected by ' . $finance->name . '. Reason: ' . $request->admin_notes,
                //     'payment_channel'   => 'System',
                //     'payment_reference' => 'WD-REJ-' . $withdraw->id,
                // ]);

                // Log rejection untuk audit
                Log::info("Withdrawal Rejected", [
                    'withdrawal_id' => $withdraw->id,
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'amount' => $withdraw->amount,
                    'reason' => $request->admin_notes,
                    'rejected_by' => $finance->name,
                    'rejected_at' => now()
                ]);

                // Siapkan pesan WhatsApp untuk rejection
                $message = "Assalamu'alaikum {$user->name},\n\n" .
                    "📋 *INFORMASI PENARIKAN DANA*\n\n" .
                    "Mohon maaf, pengajuan penarikan dana Anda sebesar *Rp {$amount}* dari akun *PT. SAIR JAYA MANDIRI* tidak dapat diproses saat ini.\n\n" .
                    "📋 *Detail Pengajuan:*\n" .
                    "• Jumlah: Rp {$amount}\n" .
                    "• Tanggal Pengajuan: " . $withdraw->created_at->format('d/m/Y H:i') . "\n" .
                    "• Status: Tidak Disetujui\n\n" .
                    "❗ *Alasan:*\n{$request->admin_notes}\n\n" .
                    "💰 *Saldo Anda tetap aman* dan dapat digunakan untuk pengajuan penarikan berikutnya setelah persyaratan terpenuhi.\n\n" .
                    "📞 *Butuh bantuan?*\n" .
                    "Silakan hubungi tim customer service kami untuk penjelasan lebih lanjut atau bantuan dalam memenuhi persyaratan penarikan.\n\n" .
                    "📌 Diproses oleh: *{$finance->name}* (Finance)\n" .
                    "🕐 Waktu Proses: " . now()->format('d/m/Y H:i') . "\n\n" .
                    "💡 *Pesan untuk Anda:*\n" .
                    "\"Setiap penolakan adalah kesempatan untuk memperbaiki dan menjadi lebih baik. Jangan menyerah!\"\n\n" .
                    "Terima kasih atas pengertian Anda. Tim *Sair Jaya Mandiri* siap membantu Anda mencapai tujuan finansial.";

                $responseMessage = 'Withdrawal ditolak. Saldo dikembalikan ke member.';
            }

            // Kirim WhatsApp
            Log::info("Sending WhatsApp to: " . $user->no_telp);
            $this->sendWhatsApp($user->no_telp, $message);

            // Log activity
            Log::info("Withdrawal {$request->action}d", [
                'withdrawal_id' => $withdraw->id,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'amount' => $withdraw->amount,
                'action' => $request->action,
                'processed_by' => $finance->name,
                'admin_notes' => $request->admin_notes ?? null,
                'transfer_reference' => $request->transfer_reference ?? null
            ]);

            return response()->json([
                'success' => true,
                'message' => $responseMessage,
                'data' => [
                    'withdrawal_id' => $withdraw->id,
                    'status' => $withdraw->status,
                    'processed_at' => $withdraw->processed_at,
                    'processed_by' => $finance->name
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error("❌ Error withdrawal process", [
                'withdrawal_id' => $id,
                'action' => $request->action ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses withdrawal. Silakan coba lagi.',
                'error' => $e->getMessage()
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
