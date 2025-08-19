<?php

namespace App\Http\Controllers\Member;

use App\Events\NotificationReceived;
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

class WithdrawController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $totalBonus = BonusTransaction::where('user_id', Auth::id())->sum('amount');
        $totalWithdrawn = Withdrawal::where('user_id', $user->id)->where('status', 'approved')->sum('amount');
        $withdrawals = Withdrawal::where('user_id', $user->id)->latest()->get();
        $mitraProfile = MitraProfile::where('user_id', $user->id)->first();
        $bonusAvailable = $totalBonus - $totalWithdrawn;


        return view('member.index', compact('withdrawals', 'bonusAvailable', 'mitraProfile'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:50000',
            'payment_channel' => 'required|string',
            'payment_details' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $user = auth()->user();
        // dd($user);
        // Validasi: Cek apakah ada withdrawal yang masih pending/processing
        $pendingWithdrawal = Withdrawal::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing']) // status yang belum selesai
            ->first();

        if ($pendingWithdrawal) {
            return response()->json([
                'success' => false,
                'message' => 'Anda masih memiliki pengajuan penarikan yang sedang diproses. Silakan tunggu hingga selesai sebelum mengajukan penarikan baru.',
                'data' => [
                    'pending_withdrawal' => [
                        'id' => $pendingWithdrawal->id,
                        'amount' => $pendingWithdrawal->amount,
                        'status' => $pendingWithdrawal->status,
                        'created_at' => $pendingWithdrawal->created_at->format('d/m/Y H:i'),
                        'status_text' => $this->getStatusText($pendingWithdrawal->status)
                    ]
                ]
            ], 422);
        }

        // Hitung bonus tersedia = total bonus - total yang sudah dicairkan (bukan yang masih menunggu)
        $totalBonus = BonusTransaction::where('user_id', $user->id)->sum('amount');
        $totalWithdrawn = Withdrawal::where('user_id', $user->id)
            ->where('status', 'processed') // hanya yang sudah ditransfer
            ->sum('amount');

        // Kurangi juga dengan withdrawal yang sedang pending/processing
        $pendingAmount = Withdrawal::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])
            ->sum('amount');

        $available = $totalBonus - $totalWithdrawn - $pendingAmount;

        if ($request->amount > $available) {
            return response()->json([
                'success' => false,
                'message' => 'Bonus tidak mencukupi untuk penarikan ini.',
                'data' => [
                    'total_bonus' => $totalBonus,
                    'total_withdrawn' => $totalWithdrawn,
                    'pending_amount' => $pendingAmount,
                    'available' => $available,
                    'requested' => $request->amount
                ]
            ], 400);
        }

        $tax = $request->amount * 0.05; // 5% pajak
        $net = $request->amount - $tax;
        // dump($net);
        // dump($tax);
        // dd($request->all());
        $withdrawal = Withdrawal::create([
            'user_id' => $user->id,
            'amount' => $request->amount,
            'tax' => $tax,
            'net_amount' => $net,
            'payment_channel' => $request->payment_channel,
            'payment_details' => $request->payment_details,
            'notes' => $request->notes,
            'status' => 'pending',
            'requested_at' => now(), // tambahan untuk tracking
        ]);

        $financeUsers = User::where('role', 'finance')->get();
        $findUser = User::find($user->id);

        foreach ($financeUsers as $finance) {
            // Simpan ke database (jika perlu histori)
            Notification::create([
                'user_id' => $finance->id,
                'message' => 'Member : ' . $findUser->name . ' Meminta pengajuan penarikan bonus',
                'url' => route('finance.withdraws.index'),
            ]);

            // Broadcast via Pusher
            event(new RequestBonusByMember($finance->id, [
                'type' => 'member_request_bonus', // atau 'preregistration_received' jika Anda ingin beda
                'message' => 'Member : ' . $findUser->name . ' Meminta pengajuan penarikan bonus',
                'url' => route('finance.withdraws.index'),
                'created_at' => now()->toDateTimeString()
            ]));
        }

        return response()->json([
            'success' => true,
            'message' => 'Penarikan berhasil diajukan. Silakan tunggu proses verifikasi dari admin.',
            'data' => [
                'withdrawal_id' => $withdrawal->id,
                'amount' => $withdrawal->amount,
                'tax' => $withdrawal->tax,
                'net_amount' => $withdrawal->net_amount,
                'status' => $withdrawal->status,
                'estimated_process_time' => '1-3 hari kerja'
            ]
        ]);
    }

    private function getStatusText($status)
    {
        $statusTexts = [
            'pending' => 'Menunggu Verifikasi',
            'menunggu' => 'Sedang Diproses',
            'approved' => 'Berhasil Ditransfer',
            'rejected' => 'Ditolak',
            'cancelled' => 'Dibatalkan'
        ];

        return $statusTexts[$status] ?? 'Status Tidak Dikenal';
    }

    public function getBonusAvailable()
    {
        $user = auth()->user();

        // Total bonus dari transaksi (netto)
        $totalBonus = BonusTransaction::where('user_id', $user->id)->sum('amount');

        // Semua withdraw yang sudah dan sedang diajukan (approved + pending)
        $totalWithdrawn = Withdrawal::where('user_id', $user->id)
            ->whereIn('status', ['approved', 'pending'])
            ->sum('amount');

        // Hitung sisa yang benar-benar bisa ditarik
        $available = max(0, $totalBonus - $totalWithdrawn);

        return response()->json([
            'bonus' => number_format($available, 0, ',', '.'), // untuk ditampilkan
            'bonus_raw' => round($available, 2)                // untuk validasi JS
        ]);
    }
}
