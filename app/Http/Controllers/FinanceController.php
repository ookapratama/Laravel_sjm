<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IncomeDetail;
use App\Models\BonusTransaction;
use App\Models\Withdrawal;
use App\Models\User;
use Carbon\Carbon;

class FinanceController extends Controller
{
    public function index()
    {
        return view('finance.index');
    }

    public function cashflowSummary()
    {
        $cashflow = IncomeDetail::selectRaw('
            DATE_FORMAT(date, "%Y-%m") as bulan,
            SUM(pendaftaran_member + produk + manajemen) as total_masuk,
            SUM(pairing_bonus + ro_bonus + reward_poin + withdraw) as total_keluar,
            SUM((pendaftaran_member + produk + manajemen) - (pairing_bonus + ro_bonus + reward_poin + withdraw)) as saldo
        ')
        ->groupBy('bulan')
        ->orderBy('bulan', 'desc')
        ->get();

        return view('finance.cashflow', compact('cashflow'));
    }

    public function rekapBonus()
    {
        $rekap = BonusTransaction::selectRaw('
            DATE_FORMAT(created_at, "%Y-%m") as bulan,
            SUM(CASE 
                WHEN type = "pairing" AND notes NOT LIKE "%Bonus RO%" THEN net_amount
                ELSE 0 END
            ) as pairing,
            SUM(CASE 
                WHEN type = "pairing" AND notes LIKE "%Bonus RO%" THEN net_amount
                ELSE 0 END
            ) as ro
        ')
        ->groupBy('bulan')
        ->orderBy('bulan', 'desc')
        ->get();

        return view('finance.bonus-rekap', compact('rekap'));
    }

    public function poinReward()
    {
        $data = User::select('id', 'name', 'username', 'pairing_point')
    ->orderByDesc('pairing_point')
    ->take(20)
    ->get();


        foreach ($data as $user) {
            $poin = $user->pairing_point;
            if ($poin >= 1700) {
                $user->reward = 'Pajero';
            } elseif ($poin >= 440) {
                $user->reward = 'Mobil Sederhana';
            } elseif ($poin >= 130) {
                $user->reward = 'Umroh';
            } elseif ($poin >= 70) {
                $user->reward = '3 Negara';
            } elseif ($poin >= 20) {
                $user->reward = 'Bali';
            } else {
                $user->reward = '-';
            }

            $user->status = ($user->reward !== '-') ? '✅ Tercapai' : '❌ Belum';
        }

        return view('finance.poin-reward', compact('data'));
    }

    public function targetPendaftaran()
    {
        $target_per_bulan = 1000;

        $pendaftaran = User::selectRaw('
            DATE_FORMAT(created_at, "%Y-%m") as bulan,
            COUNT(*) as total
        ')
        ->groupBy('bulan')
        ->orderBy('bulan', 'desc')
        ->get();

        foreach ($pendaftaran as $item) {
            $item->target = $target_per_bulan;
            $item->percent = round(($item->total / $target_per_bulan) * 100, 1);
        }
    $pendaftaranx = User::selectRaw('
            DATE_FORMAT(created_at, "%Y-%m") as bulan,
            COUNT(*) as total
        ')
        ->groupBy('bulan')
        ->orderBy('bulan', 'asc')
        ->get();

        $labels = $pendaftaranx->pluck('bulan');
        $values = $pendaftaranx->pluck('total');
        $targets = collect(array_fill(0, count($labels), $target_per_bulan));

        return view('finance.target-vs-actual', compact('pendaftaran','labels', 'values', 'targets'));
    }

    public function growthChart()
    {
        $data = User::selectRaw('
            DATE(created_at) as tanggal,
            COUNT(*) as jumlah
        ')
        ->groupBy('tanggal')
        ->orderBy('tanggal')
        ->get();

        $labels = $data->pluck('tanggal')->toArray();
        $values = $data->pluck('jumlah')->toArray();

        return view('finance.growth-chart', compact('labels', 'values'));
    }

    public function topBonus()
    {
        $data = BonusTransaction::selectRaw('user_id, SUM(net_amount) as total_bonus')
            ->groupBy('user_id')
            ->orderByDesc('total_bonus')
            ->take(10)
            ->with('user:id,name,username')
            ->get();

        return view('finance.top-bonus', compact('data'));
    }

    public function outstandingWithdraw()
    {
        $data = Withdrawal::where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->with('user:id,name,username')
            ->get();

        return view('finance.outstanding-withdraw', compact('data'));
    }
}
