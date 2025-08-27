<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\BonusTransaction;
use App\Models\IncomeDetail;
use App\Helpers\TreeHelper;
use App\Models\ActivationPin;
use App\Models\Withdrawal;
use App\Models\User;
use App\Models\Notification;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class DashboardController extends Controller
{
    public function superAdmin()
    {
        $user = Auth::user();
        $bonus_sjm = BonusTransaction::whereIn('user_id', range(1, 15))->sum('amount');
        $bonus_manajemen = BonusTransaction::whereIn('user_id', range(16, 31))->sum('amount');
        $totalMembers = User::count(); // atau where('role', 'member')
        $totalBonusnet = BonusTransaction::where('user_id', $user->id)
            ->where('status', 'paid')
            ->sum('amount');
        $userBagans = $user->bagans()->orderBy('bagan')->get();
        $userBaganAktif = $userBagans->pluck('bagan')->toArray(); // array angka: [1, 2]
        $leftDownline = TreeHelper::countDownlines($user, 'left');
        $rightDownline = TreeHelper::countDownlines($user, 'right');
        $totalBonusSJMPaid = BonusTransaction::whereIn('user_id', range(1, 15))
            ->where('status', 'paid')
            ->sum('amount');
        $totalBonusManajemenPaid = BonusTransaction::whereIn('user_id', range(16, 31))
            ->where('status', 'paid')
            ->sum('amount');
        $totalWithdrawn_sjm = Withdrawal::whereIn('user_id', range(1, 15))
            ->where('status', 'approved')
            ->sum('amount');
        $totalWithdrawn_manajemen = Withdrawal::whereIn('user_id', range(16, 31))
            ->where('status', 'approved')
            ->sum('amount');

        $saldoBonusSJMTersedia = $totalBonusSJMPaid  - $totalWithdrawn_sjm;
        $saldoBonusManajemenTersedia = $totalBonusManajemenPaid  - $totalWithdrawn_manajemen;
        return view('dashboards.super_admin', [
            'user' => $user,
            'bonus_sjm' => $bonus_sjm,
            'bonus_manajemen' => $bonus_manajemen,
            'totalMembers' => $totalMembers,
            'userBagans' => $userBagans,
            'userBaganAktif' => $userBaganAktif,
            'leftDownline' => $leftDownline,
            'rightDownline' => $rightDownline,
            'saldoBonusSJMTersedia' => $saldoBonusSJMTersedia,
            'saldoBonusManajemenTersedia' => $saldoBonusManajemenTersedia,
        ]);
    }

    public function admin()
    {
        $user = Auth::user();
        $totalBonus = BonusTransaction::where('user_id', $user->id)->sum('amount');
        $totalBonusnet = BonusTransaction::where('user_id', $user->id)
            ->where('status', 'paid')
            ->sum('amount');
        $userBagans = $user->bagans()->orderBy('bagan')->get();
        $userBaganAktif = $userBagans->pluck('bagan')->toArray(); // array angka: [1, 2]
        $leftDownline = TreeHelper::countDownlines($user, 'left');
        $rightDownline = TreeHelper::countDownlines($user, 'right');
        $totalBonusPaid = BonusTransaction::where('user_id', $user->id)
            ->where('status', 'paid')
            ->sum('net_amount');

        $totalWithdrawn = Withdrawal::where('user_id', $user->id)
            ->where('status', 'approved')
            ->sum('amount');

        $saldoBonusTersedia = $totalBonusPaid - $totalWithdrawn;
        return view('dashboards.admin', [
            'user' => $user,
            'userBagans' => $userBagans,
            'userBaganAktif' => $userBaganAktif,
            'totalBonus' => $totalBonus,
            'totalBonusnett' => $totalBonusnet,
            'leftDownline' => $leftDownline,
            'rightDownline' => $rightDownline,
            'saldoBonusTersedia' => $saldoBonusTersedia,
        ]);
    }

    public function finance()
    {
       $notifications = Notification::where('user_id', auth()->id())
        ->where('is_read', false)
        ->orderByDesc('created_at')
        ->get();

    // Rentang 30 hari terakhir (hari ini termasuk)
    $end   = Carbon::today();
    $start = (clone $end)->subDays(15);

    // Ambil data dalam rentang sekali saja
    $rows = IncomeDetail::whereBetween('date', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
        ->orderBy('date')
        ->get()
        ->groupBy(fn ($r) => Carbon::parse($r->date)->toDateString()); // key: Y-m-d

    // Buat deret tanggal lengkap (isi 0 kalau tidak ada)
    $period = CarbonPeriod::create($start, $end);
    $daily = collect($period)->map(function (Carbon $day) use ($rows) {
        $key  = $day->toDateString(); // Y-m-d
        $list = $rows->get($key, collect());

        return [
            'date'           => $key,                                  // mentah
            'label'          => $day->locale('id_ID')->isoFormat('DD MMM'), // cantik: 14 Agu
            'penjualan_pin'  => (int) $list->sum('penjualan_pin'),
            'produk'         => (int) $list->sum('produk'),
            'manajemen'      => (int) $list->sum('manajemen'),
            'pairing_bonus'  => (int) $list->sum('pairing_bonus'),
            'withdraw'       => (int) $list->sum('withdraw'),
        ];
    })->values();

    // Pie bulan ini (lebih efisien pakai whereBetween)
    $bulanIni = IncomeDetail::whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])->get();

    $incomePie = [
        'penjualan_pin' => (int) $bulanIni->sum('penjualan_pin'),
        'produk'        => (int) $bulanIni->sum('produk'),
        'manajemen'     => (int) $bulanIni->sum('manajemen'),
    ];

    $expensePie = [
        'pairing_bonus' => (int) $bulanIni->sum('pairing_bonus'),
        'withdraw'      => (int) $bulanIni->sum('withdraw'),
    ];

    return view('finance.dashboard', compact('daily', 'incomePie', 'expensePie', 'notifications'));
    }

    public function member()
    {
        try {
            //code...
            $user = Auth::user();
            $totalBonus = BonusTransaction::where('user_id', $user->id)->sum('amount');
            $totalBonusnet = BonusTransaction::where('user_id', $user->id)
                ->where('status', 'paid')
                ->sum('amount');
            $userBagans = $user->bagans()->orderBy('bagan')->get();
            $userBaganAktif = $userBagans->pluck('bagan')->toArray(); // array angka: [1, 2]
            $leftDownline = TreeHelper::countDownlines($user, 'left');
            $rightDownline = TreeHelper::countDownlines($user, 'right');
            $totalBonusPaid = BonusTransaction::where('user_id', $user->id)
                ->where('status', 'paid')
                ->sum('amount');
    
            $totalWithdrawn = Withdrawal::where('user_id', $user->id)
                ->where('status', 'approved')
                ->sum('amount');
    
            $saldoBonusTersedia = $totalBonusPaid - $totalWithdrawn;
            $pajakSaldo = $saldoBonusTersedia * 0.05;
    
            // member downline
            $downlines = ActivationPin::where('transferred_to', $user->id)->orderBy('id', 'asc')->get();
            // dd($downlines);
    
            return view('dashboards.member', [
                'user' => $user,
                'userBagans' => $userBagans,
                'userBaganAktif' => $userBaganAktif,
                'totalBonus' => $totalBonus,
                'totalBonusnett' => $saldoBonusTersedia,
                'leftDownline' => $leftDownline,
                'rightDownline' => $rightDownline,
                'saldoBonusTersedia' => $saldoBonusTersedia,
                'downlines' => $downlines ,
                // 'downlines' => [] ,
            ]);
        } catch (\Exception $e) {
            \Log::error('Gagal menyimpan user: ' . $e->getMessage());
            return response()->json([
                'message' => 'Terjadi kesalahan saat menyimpan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
