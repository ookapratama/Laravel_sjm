<?php

namespace App\Http\Controllers\Member;

use App\Events\RequestBonusByMember;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\CashTransaction;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use App\Models\BonusTransaction;
use App\Models\Withdrawal;
use App\Models\MitraProfile;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class WithdrawController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $totalBonus      = BonusTransaction::where('user_id', $user->id)->sum('amount');
        $totalWithdrawn  = Withdrawal::where('user_id', $user->id)
                                ->where('status', 'approved')
                                ->sum('amount');
        $withdrawals     = Withdrawal::where('user_id', $user->id)->latest()->get();
        $mitraProfile    = MitraProfile::where('user_id', $user->id)->first();
        $bonusAvailable  = $totalBonus - $totalWithdrawn;

        return view('member.index', compact('withdrawals', 'bonusAvailable', 'mitraProfile'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'amount'          => 'required|numeric|min:50000',
            'payment_channel' => 'required|string',
            'payment_details' => 'required|string',
            'notes'           => 'nullable|string',
        ]);

        $user = auth()->user();

        // Tolak jika masih ada pengajuan yang belum selesai
        $pendingWithdrawal = Withdrawal::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])
            ->first();

        if ($pendingWithdrawal) {
            return response()->json([
                'success' => false,
                'message' => 'Anda masih memiliki pengajuan penarikan yang sedang diproses. Silakan tunggu hingga selesai sebelum mengajukan penarikan baru.',
                'data' => [
                    'pending_withdrawal' => [
                        'id'         => $pendingWithdrawal->id,
                        'amount'     => $pendingWithdrawal->amount,
                        'status'     => $pendingWithdrawal->status,
                        'created_at' => $pendingWithdrawal->created_at->format('d/m/Y H:i'),
                        'status_text'=> $this->getStatusText($pendingWithdrawal->status),
                    ]
                ]
            ], 422);
        }

        // Hitung bonus tersedia (approved saja yang dianggap sudah cair)
        $totalBonus      = BonusTransaction::where('user_id', $user->id)->sum('amount');
        $totalWithdrawn  = Withdrawal::where('user_id', $user->id)
                                ->where('status', 'approved') // konsisten
                                ->sum('amount');
        $pendingAmount   = Withdrawal::where('user_id', $user->id)
                                ->whereIn('status', ['pending', 'processing'])
                                ->sum('amount');

        $available = $totalBonus - $totalWithdrawn - $pendingAmount;

        if ($request->amount > $available) {
            return response()->json([
                'success' => false,
                'message' => 'Bonus tidak mencukupi untuk penarikan ini.',
                'data' => [
                    'total_bonus'     => $totalBonus,
                    'total_withdrawn' => $totalWithdrawn,
                    'pending_amount'  => $pendingAmount,
                    'available'       => $available,
                    'requested'       => $request->amount,
                ]
            ], 400);
        }

        // Pajak 5%
        $tax = $request->amount * 0.05;
        $net = $request->amount - $tax;

        $withdrawal = Withdrawal::create([
            'user_id'         => $user->id,
            'amount'          => $request->amount,
            'tax'             => $tax,
            'net_amount'      => $net,
            'payment_channel' => $request->payment_channel,
            'payment_details' => $request->payment_details,
            'notes'           => $request->notes,
            'status'          => 'pending',
            'requested_at'    => now(),
        ]);

        // Notifikasi ke seluruh user role finance (DB + Broadcast + WhatsApp menggunakan users.no_hp)
        $this->notifyFinanceUsers($withdrawal, $user);

        return response()->json([
            'success' => true,
            'message' => 'Penarikan berhasil diajukan. Silakan tunggu proses verifikasi dari admin.',
            'data' => [
                'withdrawal_id'        => $withdrawal->id,
                'amount'               => $withdrawal->amount,
                'tax'                  => $withdrawal->tax,
                'net_amount'           => $withdrawal->net_amount,
                'status'               => $withdrawal->status,
                'estimated_process_time'=> '1-3 hari kerja',
            ]
        ]);
    }

    private function getStatusText($status)
    {
        $statusTexts = [
            'pending'    => 'Menunggu Verifikasi',
            'processing' => 'Sedang Diproses',
            'approved'   => 'Berhasil Ditransfer',
            'rejected'   => 'Ditolak',
            'cancelled'  => 'Dibatalkan',
        ];

        return $statusTexts[$status] ?? 'Status Tidak Dikenal';
    }

    public function getBonusAvailable()
    {
        $user = auth()->user();

        $totalBonus     = BonusTransaction::where('user_id', $user->id)->sum('amount');
        $totalWithdrawn = Withdrawal::where('user_id', $user->id)
                                ->whereIn('status', ['approved', 'pending'])
                                ->sum('amount');

        $available = max(0, $totalBonus - $totalWithdrawn);

        return response()->json([
            'bonus'     => number_format($available, 0, ',', '.'),
            'bonus_raw' => round($available, 2),
        ]);
    }

    /**
     * Kirim notifikasi ke seluruh user role=finance:
     * - Simpan notifikasi DB
     * - Broadcast event (Pusher/Websocket)
     * - WhatsApp ke nomor users.no_hp (auto +62 jika diawali 0 pada sendWhatsApp)
     */
    private function notifyFinanceUsers(Withdrawal $withdrawal, User $member): void
    {
        $finances = User::where('role', 'finance')->get();
        $gross   = number_format($withdrawal->amount, 0, ',', '.');
        $fee     = number_format($withdrawal->tax, 0, ',', '.');
        $net     = number_format($withdrawal->amount - $withdrawal->tax, 0, ',', '.');
        $when    = $withdrawal->created_at->format('d/m/Y H:i');
        $userTag = $member->username ?? $member->email;
        $notes   = $withdrawal->notes ?: '-';
        $ref     = $withdrawal->id;
       $msg = sprintf(
            "🟡 *Permintaan Withdraw Baru*\n\n".
            "Ref: *WD#%s*\n".
            "Member : *%s* (@%s)\n".
            "Jumlah : *Rp%s*\n".
            "Biaya Admin : Rp%s (5%%)\n".
            "Diterima : *Rp%s*\n".
            "Waktu : %s\n".
            "Catatan : %s\n\n".
            "👉 Mohon verifikasi dan proses di *Finance Dashboard*.",
            $ref, $member->name, $userTag, $gross, $fee, $net, $when, $notes
        );

        foreach ($finances as $f) {
            // 1) Notifikasi DB
            Notification::create([
                'user_id' => $f->id,
                'message' => 'Permintaan withdraw baru dari: '.$member->name.' (Rp'.number_format($withdrawal->amount, 0, ',', '.').')',
                'url'     => route('finance.withdraws.index'),
            ]);

            // 2) Broadcast realtime
            event(new RequestBonusByMember($f->id, [
                'type'       => 'member_request_bonus',
                'message'    => 'Permintaan withdraw dari '.$member->name.' sejumlah Rp'.number_format($withdrawal->amount, 0, ',', '.'),
                'url'        => route('finance.withdraws.index'),
                'created_at' => now()->toDateTimeString(),
            ]));
            // 3) WhatsApp ke no_hp user finance (jika ada)
            if (!empty($f->no_telp)) {
                Log::info('kirim pesan ke finance', [
                    'finance_id' => $f->id,
                    'no_hp'      => $f->no_telp,
                ]);

                $this->sendWhatsApp($f->no_telp, $msg);
            } else {
                Log::warning('no_hp finance kosong, WA tidak dikirim', [
                    'finance_id' => $f->id,
                ]);
            }
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
