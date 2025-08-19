<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IncomeDetail;
use App\Models\BonusTransaction;
use App\Models\Withdrawal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            SUM(penjualan_pin + produk + manajemen) as total_masuk,
            SUM(pairing_bonus + ro_bonus + reward_poin + withdraw) as total_keluar,
            SUM((penjualan_pin + produk + manajemen) - (pairing_bonus + ro_bonus + reward_poin + withdraw)) as saldo
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

        return view('finance.target-vs-actual', compact('pendaftaran', 'labels', 'values', 'targets'));
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

    public function withdrawalHistoryPage()
    {
        return view('finance.report-withdraw');
    }

    // Method untuk search users
    public function searchUsers(Request $request)
    {
        $query = $request->get('q');

        if (strlen($query) < 2) {
            return response()->json(['success' => false, 'message' => 'Query too short']);
        }

        $users = DB::select("
        SELECT 
            u.id,
            u.name,
            u.email,
            u.member_id,
            COUNT(w.id) as withdrawal_count
        FROM users u
        LEFT JOIN withdrawals w ON u.id = w.user_id
        WHERE u.name LIKE ? 
           OR u.email LIKE ? 
           OR u.member_id LIKE ?
        GROUP BY u.id, u.name, u.email, u.member_id
        ORDER BY withdrawal_count DESC, u.name ASC
        LIMIT 20
    ", ["%{$query}%", "%{$query}%", "%{$query}%"]);

        return response()->json([
            'success' => true,
            'users' => $users
        ]);
    }

    public function getUserWithdrawalHistory($userId)
    {
        try {
            $history = DB::select("
            SELECT 
                w.id,
                w.amount,
                w.status,
                w.admin_notes,
                w.transfer_reference,
                w.created_at,
                w.processed_at,
                finance.name as processed_by_name
            FROM withdrawals w
            LEFT JOIN users finance ON w.processed_by = finance.id
            WHERE w.user_id = ?
            ORDER BY w.created_at DESC
          
        ", [$userId]);

            $summary = DB::select("
            SELECT 
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_count,
                COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_count,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                COALESCE(SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END), 0) as total_approved_amount,
                COALESCE(SUM(CASE WHEN status = 'rejected' THEN amount ELSE 0 END), 0) as total_rejected_amount
            FROM withdrawals
            WHERE user_id = ?
        ", [$userId]);

            return response()->json([
                'success' => true,
                'history' => $history,
                'summary' => $summary[0] ?? null
            ]);
        } catch (\Exception $e) {
            Log::error("Error getting user withdrawal history", [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat history withdrawal'
            ], 500);
        }
    }

    public function getWithdrawalDetail($id)
    {
        try {
            $withdrawal = DB::select("
            SELECT 
                w.*,
                u.id as user_id,
                u.name as user_name,
                u.email,
                u.no_telp,
                mp.nomor_rekening,
                mp.nama_bank,
                mp.nama_rekening,
                
                -- Calculate user balance
                (SELECT COALESCE(SUM(bt.net_amount), 0) 
                 FROM bonus_transactions bt 
                 WHERE bt.user_id = u.id AND bt.status = 'active') as total_bonus,
                
                (SELECT COALESCE(SUM(w2.amount), 0) 
                 FROM withdrawals w2 
                 WHERE w2.user_id = u.id AND w2.status IN ('approved', 'processed')) as total_withdrawn,
                
                (SELECT COALESCE(SUM(w3.amount), 0) 
                 FROM withdrawals w3 
                 WHERE w3.user_id = u.id AND w3.status = 'pending' AND w3.id != w.id) as other_pending
                
            FROM withdrawals w
            INNER JOIN users u ON w.user_id = u.id
            LEFT JOIN mitra_profiles mp ON u.id = mp.user_id
            WHERE w.id = ?
        ", [$id]);

            if (empty($withdrawal)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Withdrawal tidak ditemukan'
                ], 404);
            }

            $data = $withdrawal[0];

            // Calculate available balance
            $available = $data->total_bonus - $data->total_withdrawn - $data->other_pending;

            return response()->json([
                'success' => true,
                'data' => $data,
                'balance' => [
                    'total_bonus' => $data->total_bonus,
                    'total_withdrawn' => $data->total_withdrawn,
                    'pending' => $data->other_pending,
                    'available' => max(0, $available)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("Error getting withdrawal detail", [
                'withdrawal_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat detail withdrawal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    // Method untuk semua withdrawal
    public function getAllWithdrawals(Request $request)
    {
        try {
            //code...
            $limit = $request->get('limit', 100);
            // $status = $request->get('status') ?? '';
            // $date = $request->get('date') ?? '';

            $query = "
            SELECT 
                w.id as withdrawal_id,
                w.amount,
                w.status,
                w.transfer_reference,
                w.created_at as withdrawal_date,
                w.processed_at,
                w.admin_notes,
                
                u.id as user_id,
                u.name as user_name,
                u.email as user_email
                
            FROM withdrawals w
            INNER JOIN users u ON w.user_id = u.id
            WHERE 1=1
        ";

            $params = [];

            // if ($status) {
            //     $query .= " AND w.status = ?";
            //     $params[] = $status;
            // }

            // if ($date) {
            //     $query .= " AND DATE(w.created_at) = ?";
            //     $params[] = $date;
            // }

            $query .= " ORDER BY w.created_at DESC LIMIT ?";
            $params[] = (int)$limit;

            $withdrawals = DB::select($query, $params);
            return response()->json([
                'success' => true,
                'withdrawals' => $withdrawals
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        }
    }

    // Method untuk statistik hari ini
    public function getTodayStats()
    {
        $today = date('Y-m-d');

        $stats = DB::select("
        SELECT 
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
            COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
            COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected
        FROM withdrawals
        WHERE DATE(created_at) = ?
    ", [$today]);

        return response()->json([
            'success' => true,
            'stats' => $stats[0] ?? ['pending' => 0, 'approved' => 0, 'rejected' => 0]
        ]);
    }
}
