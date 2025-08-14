<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\BonusTransaction;
use App\Models\IncomeDetail;
use App\Helpers\TreeHelper;
use App\Models\Withdrawal;
use App\Models\User;
use App\Models\Notification;
use Carbon\Carbon;

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

        $data = IncomeDetail::orderBy('date')->get();

        $daily = $data->sortBy('date')->take(-30)->values(); // ambil 30 hari terakhir

        $currentMonth = Carbon::now()->format('Y-m');
        $bulanIni = $data->filter(fn($i) => $i->date->format('Y-m') === $currentMonth);

        $incomePie = [
            'penjualan_pin' => $bulanIni->sum('penjualan_pin'),
            'produk' => $bulanIni->sum('produk'),
            'manajemen' => $bulanIni->sum('manajemen'),
        ];

        $expensePie = [
            'pairing_bonus' => $bulanIni->sum('pairing_bonus'),
            'withdraw' => $bulanIni->sum('withdraw'),
        ];

        return view('finance.dashboard', compact('daily', 'incomePie', 'expensePie', 'notifications'));
    }

    public function member()
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
            ->sum('amount');

        $totalWithdrawn = Withdrawal::where('user_id', $user->id)
            ->where('status', 'approved')
            ->sum('amount');

        $saldoBonusTersedia = $totalBonusPaid - $totalWithdrawn;
        $pajakSaldo = $saldoBonusTersedia * 0.05;
        // dd($saldoBonusTersedia - $pajakSaldo);
        return view('dashboards.member', [
            'user' => $user,
            'userBagans' => $userBagans,
            'userBaganAktif' => $userBaganAktif,
            'totalBonus' => $totalBonus,
            'totalBonusnett' => $saldoBonusTersedia,
            'leftDownline' => $leftDownline,
            'rightDownline' => $rightDownline,
            'saldoBonusTersedia' => $saldoBonusTersedia,
        ]);
    }
}
