<?php

namespace App\Http\Controllers\Member;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\CashTransaction;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use App\Models\BonusTransaction;
use App\Models\Withdrawal;
use App\Models\MitraProfile;
class WithdrawController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $totalBonus =BonusTransaction::where('user_id', Auth::id())->sum('net_amount');
        $totalWithdrawn = Withdrawal::where('user_id', $user->id)->where('status', 'approved')->sum('amount');
            $withdrawals = Withdrawal::where('user_id', $user->id)->latest()->get();
            $mitraProfile = MitraProfile::where('user_id', $user->id)->first();
            $bonusAvailable=$totalBonus-$totalWithdrawn;
            

    return view('member.index', compact('withdrawals', 'bonusAvailable','mitraProfile'));
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

    // Hitung bonus tersedia = total bonus - total yang sudah dicairkan (bukan yang masih menunggu)
    $totalBonus = BonusTransaction::where('user_id', $user->id)->sum('net_amount');
    $totalWithdrawn = Withdrawal::where('user_id', $user->id)
        ->where('status', 'processed') // hanya yang sudah ditransfer
        ->sum('amount');
    $available = $totalBonus - $totalWithdrawn;

    if ($request->amount > $available) {
        return response()->json([
            'success' => false,
            'message' => 'Bonus tidak mencukupi untuk penarikan ini.',
        ], 400);
    }

    $tax = $request->amount * 0.05; // 5% pajak
    $net = $request->amount - $tax;

    Withdrawal::create([
        'user_id' => $user->id,
        'amount' => $request->amount,
        'tax' => $tax,
        'net_amount' => $net,
        'payment_channel' => $request->payment_channel,
        'payment_details' => $request->payment_details,
        'notes' => $request->notes,
        'status' => 'pending', // tetap pending di awal
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Penarikan berhasil diajukan.',
    ]);
}

public function getBonusAvailable()
{
    $user = auth()->user();

    // Total bonus dari transaksi (netto)
    $totalBonus = BonusTransaction::where('user_id', $user->id)->sum('net_amount');

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
