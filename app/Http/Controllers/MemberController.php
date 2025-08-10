<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\BonusTransaction;
use App\Models\Withdrawal;
class MemberController extends Controller
{
     public function index()
    {
        
        return view('member.index');
    }

public function upgradeBagan(Request $request, $bagan)
{
    $user = auth()->user();

    $request->validate([
        'metode_pembayaran' => 'required|in:bonus,transfer',
        'bukti_transfer' => 'required_if:metode_pembayaran,transfer|file|mimes:jpg,jpeg,png,pdf|max:2048',
    ]);

    $biaya = [
        1 => 1500000,
        2 => 3000000,
        3 => 6000000,
        4 => 12000000,
        5 => 24000000,
    ];

    $upgradeCost = $biaya[$bagan] ?? null;
    if (!$upgradeCost) {
        return response()->json(['success' => false, 'message' => 'Bagan tidak valid']);
    }

    // Cek apakah sudah punya bagan ini
    $existing = $user->bagans()->where('bagan', $bagan)->first();

    if ($existing) {
    if ($existing->is_active) {
        return response()->json(['success' => false, 'message' => 'Bagan ini sudah aktif.']);
    }

 if ($request->metode_pembayaran === 'bonus') {
    // Hitung saldo bonus
    $totalBonus = BonusTransaction::where('user_id', $user->id)
        ->where('status', 'paid')
        ->sum('net_amount');

    $totalWithdrawn = Withdrawal::where('user_id', $user->id)
        ->where('status', 'approved')
        ->sum('amount');

    $saldo = $totalBonus - $totalWithdrawn;

    if ($saldo < $upgradeCost) {
        return response()->json([
            'success' => false,
            'message' => "Saldo bonus tidak cukup.",
        ]);
    }

    // Catat ke withdrawal langsung
    Withdrawal::create([
        'user_id' => $user->id,
        'amount' => $upgradeCost,
        'status' => 'approved',
        'type' => 'upgrade',
        'admin_notes' => "Upgrade bagan {$bagan}",
        'transfer_reference' => 'UPG-' . strtoupper(Str::random(8)),
    ]);

    $existing->update([
            'is_active' => true,
            'status' => 'approved',
            'upgrade_paid_manually' => false,
        ]);
}

    if ($request->metode_pembayaran === 'transfer') {
        $file = $request->file('bukti_transfer');
        $filename = 'bukti_transfer_' . $user->id . '_' . now()->format('Ymd_His') . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('bukti_transfers', $filename, 'public');

        $existing->update([
            'bukti_transfer' => $path,
            'upgrade_paid_manually' => true,
            'metode_pembayaran' => 'transfer',
            'status' => 'pending',
        ]);

        return response()->json(['success' => true, 'message' => 'Bukti transfer berhasil diunggah. Menunggu verifikasi admin.']);
    }

    return response()->json(['success' => true, 'message' => 'Permintaan berhasil diproses.']);
}

    // Buat data baru jika belum ada
    $data = [
        'bagan' => $bagan,
        'upgrade_cost' => $upgradeCost,
        'allocated_from_bonus' => 0,
        'upgrade_paid_manually' => false,
        'is_active' => false,
        'status' => 'pending',
        'metode_pembayaran' => $request->metode_pembayaran,
    ];

    if ($request->metode_pembayaran === 'transfer') {
        $file = $request->file('bukti_transfer');
        $filename = 'bukti_transfer_' . $user->id . '_' . now()->format('Ymd_His') . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('bukti_transfers', $filename, 'public');

        $data['bukti_transfer'] = $path;
        $data['upgrade_paid_manually'] = true;
    }

    $user->bagans()->create($data);

    return response()->json([
        'success' => true,
        'message' => 'Permintaan upgrade berhasil dikirim. Menunggu verifikasi admin.'
    ]);
}

public function cekSaldo(Request $request, $bagan)
{
    $user = auth()->user();

    $biaya = [
        1 => 1500000,
        2 => 3000000,
        3 => 6000000,
        4 => 12000000,
        5 => 24000000,
    ];

    $upgradeCost = $biaya[$bagan] ?? null;
    if (!$upgradeCost) return response()->json(['success' => false, 'message' => 'Bagan tidak valid']);

    $totalBonus = BonusTransaction::where('user_id', $user->id)
        ->where('status', 'paid')
        ->sum('net_amount');

    $totalWithdrawn = Withdrawal::where('user_id', $user->id)
        ->where('status', 'approved')
        ->sum('amount');

    $saldo = $totalBonus - $totalWithdrawn;

    if ($saldo >= $upgradeCost) {
        return response()->json(['success' => true]);
    }

    return response()->json([
        'success' => false,
        'message' => "Saldo bonus Anda (Rp" . number_format($saldo, 0, ',', '.') . ") belum mencukupi untuk upgrade. Silakan pilih metode transfer.",
    ]);
}
}
