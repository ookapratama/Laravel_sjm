<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

use App\Models\User;
use App\Models\Withdrawal;
use App\Models\IncomeDetail;
use App\Models\BonusTransaction;
use App\Models\ActivationPin;

class FinanceController extends Controller
{
    /** ===== Konstanta Bisnis PIN ===== */
    private const PIN_PRICE    = 750000; // harga per ID
    private const ALLOC_BONUS  = 500000; // pembonusan
    private const ALLOC_PRODUK = 150000; // produk
    private const ALLOC_MGMT   = 100000; // manajemen

    /** ===== Pengaturan Waktu ===== */
    private const CREATED_AT_IS_UTC = false;          // set true jika timestamp disimpan UTC
    private const APP_TZ            = 'Asia/Makassar';
    private const MYSQL_TZ_OFFSET   = '+08:00';       // untuk CONVERT_TZ jika pakai UTC

    /** ===== Helpers ===== */
    private static function dateExpr(): string
    {
        return self::CREATED_AT_IS_UTC
            ? "DATE(CONVERT_TZ(created_at, '+00:00', '".self::MYSQL_TZ_OFFSET."'))"
            : "DATE(created_at)";
    }

    private static function monthExpr(): string
    {
        return self::CREATED_AT_IS_UTC
            ? "DATE_FORMAT(CONVERT_TZ(created_at, '+00:00', '".self::MYSQL_TZ_OFFSET."'), '%Y-%m')"
            : "DATE_FORMAT(created_at, '%Y-%m')";
    }

    private static function applyDateFilter($q, ?string $from, ?string $to): void
    {
        if (self::CREATED_AT_IS_UTC) {
            if ($from) $q->where('created_at', '>=', Carbon::parse($from.' 00:00:00', self::APP_TZ)->utc());
            if ($to)   $q->where('created_at', '<=', Carbon::parse($to.' 23:59:59', self::APP_TZ)->utc());
        } else {
            if ($from) $q->whereDate('created_at', '>=', $from);
            if ($to)   $q->whereDate('created_at', '<=', $to);
        }
    }

    /** =======================
     *  DASHBOARD/INDEX
     *  ======================= */
    public function index()
    {
        return view('finance.index');
    }

    /** =======================
     *  CASHFLOW SUMMARY (IncomeDetail)
     *  ======================= */
    public function cashflowSummary()
    {
        $cashflow = IncomeDetail::selectRaw("
                DATE_FORMAT(date, '%Y-%m') AS bulan,
                SUM(penjualan_pin + produk + manajemen) AS total_masuk,
                SUM(pairing_bonus + ro_bonus + reward_poin + withdraw) AS total_keluar,
                SUM((penjualan_pin + produk + manajemen) - (pairing_bonus + ro_bonus + reward_poin + withdraw)) AS saldo
            ")
            ->groupBy('bulan')
            ->orderByDesc('bulan') // ✅ aman untuk ONLY_FULL_GROUP_BY
            ->get();

        return view('finance.cashflow', compact('cashflow'));
    }

    /** =======================
     *  REKAP BONUS (BonusTransaction)
     *  ======================= */
    public function rekapBonus()
    {
        $rekap = BonusTransaction::selectRaw("
                DATE_FORMAT(created_at, '%Y-%m') AS bulan,
                SUM(CASE WHEN type='pairing' AND notes NOT LIKE '%Bonus RO%' THEN net_amount ELSE 0 END) AS pairing,
                SUM(CASE WHEN type='pairing' AND notes LIKE '%Bonus RO%' THEN net_amount ELSE 0 END)           AS ro
            ")
            ->groupBy('bulan')
            ->orderByDesc('bulan') // ✅
            ->get();

        return view('finance.bonus-rekap', compact('rekap'));
    }

    /** =======================
     *  POIN REWARD (Top 20)
     *  ======================= */
    public function poinReward()
    {
        $data = User::select('id','name','username','pairing_point')
            ->orderByDesc('pairing_point')
            ->take(20)
            ->get();

        foreach ($data as $user) {
            $poin = (int) $user->pairing_point;
            $user->reward = match (true) {
                $poin >= 1700 => 'Pajero',
                $poin >= 440  => 'Mobil Sederhana',
                $poin >= 130  => 'Umroh',
                $poin >= 70   => '3 Negara',
                $poin >= 20   => 'Bali',
                default       => '-',
            };
            $user->status = ($user->reward !== '-') ? '✅ Tercapai' : '❌ Belum';
        }

        return view('finance.poin-reward', compact('data'));
    }

    /** =======================
     *  TARGET PENDAFTARAN
     *  ======================= */
    public function targetPendaftaran()
    {
        $target_per_bulan = 1000;

        $pendaftaran = User::selectRaw("DATE_FORMAT(created_at, '%Y-%m') AS bulan, COUNT(*) AS total")
            ->groupBy('bulan')
            ->orderByDesc('bulan') // ✅
            ->get();

        foreach ($pendaftaran as $item) {
            $item->target  = $target_per_bulan;
            $item->percent = round(($item->total / $target_per_bulan) * 100, 1);
        }

        $pendaftaranx = User::selectRaw("DATE_FORMAT(created_at, '%Y-%m') AS bulan, COUNT(*) AS total")
            ->groupBy('bulan')
            ->orderBy('bulan', 'asc')
            ->get();

        $labels  = $pendaftaranx->pluck('bulan');
        $values  = $pendaftaranx->pluck('total');
        $targets = collect(array_fill(0, count($labels), $target_per_bulan));

        return view('finance.target-vs-actual', compact('pendaftaran','labels','values','targets'));
    }

    /** =======================
     *  GROWTH CHART (harian)
     *  ======================= */
    public function growthChart()
    {
        $data = User::selectRaw("DATE(created_at) AS tanggal, COUNT(*) AS jumlah")
            ->groupBy('tanggal')
            ->orderBy('tanggal') // alias aman
            ->get();

        $labels = $data->pluck('tanggal')->toArray();
        $values = $data->pluck('jumlah')->toArray();

        return view('finance.growth-chart', compact('labels','values'));
    }

    /** =======================
     *  TOP BONUS
     *  ======================= */
    public function topBonus()
    {
        $data = BonusTransaction::selectRaw('user_id, SUM(net_amount) AS total_bonus')
            ->groupBy('user_id')
            ->orderByDesc('total_bonus')
            ->take(10)
            ->with('user:id,name,username')
            ->get();

        return view('finance.top-bonus', compact('data'));
    }

    /** =======================
     *  OUTSTANDING WITHDRAW
     *  ======================= */
    public function outstandingWithdraw()
    {
        $data = Withdrawal::where('status', 'pending')
            ->orderByDesc('created_at')
            ->with('user:id,name,username')
            ->get();

        return view('finance.outstanding-withdraw', compact('data'));
    }

    public function withdrawalHistoryPage()
    {
        return view('finance.report-withdraw');
    }

    /** =======================
     *  SEARCH USERS (for withdraw)
     *  ======================= */
    public function searchUsers(Request $request)
    {
        $query = $request->get('q');
        if (strlen($query) < 2) {
            return response()->json(['success' => false, 'message' => 'Query too short']);
        }

        $users = DB::select("
            SELECT 
                u.id, u.name, u.email, u.member_id,
                COUNT(w.id) AS withdrawal_count
            FROM users u
            LEFT JOIN withdrawals w ON u.id = w.user_id
            WHERE u.name LIKE ? OR u.email LIKE ? OR u.member_id LIKE ?
            GROUP BY u.id, u.name, u.email, u.member_id
            ORDER BY withdrawal_count DESC, u.name ASC
            LIMIT 20
        ", ["%{$query}%","%{$query}%","%{$query}%"]);

        return response()->json(['success' => true, 'users' => $users]);
    }

    public function getUserWithdrawalHistory($userId)
    {
        try {
            $history = DB::select("
                SELECT 
                    w.id, w.amount, w.status, w.admin_notes, w.transfer_reference,
                    w.created_at, w.processed_at,
                    finance.name AS processed_by_name
                FROM withdrawals w
                LEFT JOIN users finance ON w.processed_by = finance.id
                WHERE w.user_id = ?
                ORDER BY w.created_at DESC
            ", [$userId]);

            $summary = DB::select("
                SELECT 
                    COUNT(CASE WHEN status='approved' THEN 1 END) AS approved_count,
                    COUNT(CASE WHEN status='rejected' THEN 1 END) AS rejected_count,
                    COUNT(CASE WHEN status='pending'  THEN 1 END) AS pending_count,
                    COALESCE(SUM(CASE WHEN status='approved' THEN amount ELSE 0 END),0) AS total_approved_amount,
                    COALESCE(SUM(CASE WHEN status='rejected' THEN amount ELSE 0 END),0) AS total_rejected_amount
                FROM withdrawals
                WHERE user_id = ?
            ", [$userId]);

            return response()->json([
                'success' => true,
                'history' => $history,
                'summary' => $summary[0] ?? null
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting user withdrawal history', ['user_id'=>$userId, 'error'=>$e->getMessage()]);
            return response()->json(['success'=>false,'message'=>'Gagal memuat history withdrawal'], 500);
        }
    }

    public function getWithdrawalDetail($id)
    {
        try {
            $withdrawal = DB::select("
                SELECT 
                    w.*,
                    u.id AS user_id, u.name AS user_name, u.email, u.no_telp,
                    mp.nomor_rekening, mp.nama_bank, mp.nama_rekening,
                    (SELECT COALESCE(SUM(bt.net_amount),0) FROM bonus_transactions bt WHERE bt.user_id=u.id AND bt.status='active') AS total_bonus,
                    (SELECT COALESCE(SUM(w2.amount),0)  FROM withdrawals w2 WHERE w2.user_id=u.id AND w2.status IN ('approved','processed')) AS total_withdrawn,
                    (SELECT COALESCE(SUM(w3.amount),0)  FROM withdrawals w3 WHERE w3.user_id=u.id AND w3.status='pending' AND w3.id != w.id) AS other_pending
                FROM withdrawals w
                INNER JOIN users u ON w.user_id=u.id
                LEFT JOIN mitra_profiles mp ON u.id=mp.user_id
                WHERE w.id=?
            ", [$id]);

            if (empty($withdrawal)) {
                return response()->json(['success'=>false,'message'=>'Withdrawal tidak ditemukan'], 404);
            }

            $data      = $withdrawal[0];
            $available = ($data->total_bonus - $data->total_withdrawn - $data->other_pending);

            return response()->json([
                'success' => true,
                'data'    => $data,
                'balance' => [
                    'total_bonus'    => $data->total_bonus,
                    'total_withdrawn'=> $data->total_withdrawn,
                    'pending'        => $data->other_pending,
                    'available'      => max(0, $available),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting withdrawal detail', ['withdrawal_id'=>$id, 'error'=>$e->getMessage()]);
            return response()->json([
                'success'=>false,
                'message'=>'Gagal memuat detail withdrawal',
                'error'=>$e->getMessage(),
            ], 500);
        }
    }

    public function getAllWithdrawals(Request $request)
    {
        try {
            $limit = (int) $request->get('limit', 100);

            $withdrawals = DB::select("
                SELECT 
                    w.id AS withdrawal_id, w.amount, w.status, w.transfer_reference,
                    w.created_at AS withdrawal_date, w.processed_at, w.admin_notes,
                    u.id AS user_id, u.name AS user_name, u.email AS user_email
                FROM withdrawals w
                INNER JOIN users u ON w.user_id=u.id
                ORDER BY w.created_at DESC
                LIMIT ?
            ", [$limit]);

            return response()->json(['success'=>true,'withdrawals'=>$withdrawals]);
        } catch (\Exception $e) {
            return response()->json(['message'=>$e->getMessage()], 500);
        }
    }

    public function getTodayStats()
    {
        $today = date('Y-m-d');

        $stats = DB::select("
            SELECT 
                COUNT(CASE WHEN status='pending' THEN 1 END)  AS pending,
                COUNT(CASE WHEN status='approved' THEN 1 END) AS approved,
                COUNT(CASE WHEN status='rejected' THEN 1 END) AS rejected
            FROM withdrawals
            WHERE DATE(created_at) = ?
        ", [$today]);

        return response()->json([
            'success'=>true,
            'stats'  => $stats[0] ?? ['pending'=>0,'approved'=>0,'rejected'=>0]
        ]);
    }

    /** =======================
     *  REPORT KAS (cash_transactions + PIN)
     *  ======================= */
    public function cashReport(Request $r)
    {
        $from = $r->input('from');
        $to   = $r->input('to');

        // Base transaksi kas
        $qTx = DB::table('cash_transactions');
        self::applyDateFilter($qTx, $from, $to);

        // Ringkasan harian
        $daily = (clone $qTx)
            ->selectRaw(self::dateExpr()." AS tanggal,
                SUM(CASE WHEN type='in'  THEN amount ELSE 0 END) AS total_in,
                SUM(CASE WHEN type='out' THEN amount ELSE 0 END) AS total_out,
                SUM(CASE WHEN type='in'  THEN amount ELSE 0 END) - SUM(CASE WHEN type='out' THEN amount ELSE 0 END) AS saldo")
            ->groupByRaw(self::dateExpr())
            ->orderByDesc('tanggal')
            ->get();

        // Ringkasan bulanan (ONLY_FULL_GROUP_BY safe)
        $monthly = (clone $qTx)
            ->selectRaw(self::monthExpr()." AS bulan,
                SUM(CASE WHEN type='in'  THEN amount ELSE 0 END) AS total_in,
                SUM(CASE WHEN type='out' THEN amount ELSE 0 END) AS total_out,
                SUM(CASE WHEN type='in'  THEN amount ELSE 0 END) - SUM(CASE WHEN type='out' THEN amount ELSE 0 END) AS saldo")
            ->groupByRaw(self::monthExpr())
            ->orderByDesc('bulan') // ✅ ganti dari STR_TO_DATE
            ->get();

        // Saldo akhir global (dalam rentang)
        $totals = (clone $qTx)
            ->selectRaw("SUM(CASE WHEN type='in' THEN amount ELSE 0 END) AS total_in,
                         SUM(CASE WHEN type='out' THEN amount ELSE 0 END) AS total_out")
            ->first();
        $saldoAkhir = (float)($totals->total_in ?? 0) - (float)($totals->total_out ?? 0);

        // ===== Komposisi PIN (berdasarkan ActivationPin) =====
        $qPin = ActivationPin::query();
        self::applyDateFilter($qPin, $from, $to);

        $pinMonthlyCounts = (clone $qPin)
            ->selectRaw(self::monthExpr()." AS bulan, COUNT(*) AS pin_count")
            ->groupByRaw(self::monthExpr())
            ->orderByDesc('bulan') // ✅
            ->pluck('pin_count', 'bulan');

        $pinTotalCount = (clone $qPin)->count();
        $pinAlloc = [
            'pin_total'    => $pinTotalCount * self::PIN_PRICE,
            'pin_qty'      => $pinTotalCount,
            'bonus_total'  => $pinTotalCount * self::ALLOC_BONUS,
            'produk_total' => $pinTotalCount * self::ALLOC_PRODUK,
            'mgmt_total'   => $pinTotalCount * self::ALLOC_MGMT,
        ];

        $pinMonthly = collect($pinMonthlyCounts)->map(function ($qty, $bulan) {
            return (object)[
                'bulan'        => $bulan,
                'pin_total'    => $qty * self::PIN_PRICE,
                'pin_qty'      => $qty,
                'bonus_total'  => $qty * self::ALLOC_BONUS,
                'produk_total' => $qty * self::ALLOC_PRODUK,
                'mgmt_total'   => $qty * self::ALLOC_MGMT,
            ];
        })->values();

        // Pengeluaran lain-lain per bulan
        $qOther = DB::table('cash_transactions')->where('type','out');
        self::applyDateFilter($qOther, $from, $to);
        // ->where('source','lain-lain') // aktifkan kalau hanya mau lain-lain
        // ->whereNotIn('source',['produk','manajemen']); // aktifkan jika kamu catat OUT ini terpisah agar tidak double-count

        $otherOutMonthly = (clone $qOther)
            ->selectRaw(self::monthExpr()." AS bulan, COALESCE(SUM(amount),0) AS other_out")
            ->groupByRaw(self::monthExpr())
            ->orderByDesc('bulan') // ✅
            ->pluck('other_out', 'bulan');

        // Cash Flow bulanan (Cash In = PIN×750k; Cash Out = Produk×150k + Mgmt×100k + Lain-lain)
        $months = collect($pinMonthlyCounts)->keys()
            ->merge(collect($otherOutMonthly)->keys())
            ->unique()->sortDesc()->values();

        $monthlyCashFlow = $months->map(function ($bulan) use ($pinMonthlyCounts, $otherOutMonthly) {
            $pinQty       = (int)($pinMonthlyCounts[$bulan] ?? 0);
            $cashIn       = $pinQty * self::PIN_PRICE;

            $biayaProduk  = $pinQty * self::ALLOC_PRODUK;
            $biayaMgmt    = $pinQty * self::ALLOC_MGMT;
            $otherOut     = (float)($otherOutMonthly[$bulan] ?? 0);

            $cashOut      = $biayaProduk + $biayaMgmt + $otherOut;
            $saldoAkhir   = $cashIn - $cashOut;

            return (object)[
                'bulan'            => $bulan,
                'pin_count'        => $pinQty,
                'cash_in'          => $cashIn,
                'biaya_produk'     => $biayaProduk,
                'biaya_manajemen'  => $biayaMgmt,
                'pengeluaran_lain' => $otherOut,
                'cash_out'         => $cashOut,
                'saldo_akhir'      => $saldoAkhir,
            ];
        });

        return view('finance.cash_report', compact(
            'from','to',
            'daily','monthly','saldoAkhir',
            'pinAlloc','pinMonthly',
            'monthlyCashFlow'
        ));
    }

    /** =======================
     *  INPUT PENGELUARAN LAIN-LAIN
     *  ======================= */
    public function storeOtherExpense(Request $r)
    {
        $data = $r->validate([
            'date'              => ['required','date'],
            'amount'            => ['required','numeric','min:0.01'],
            'notes'             => ['nullable','string','max:500'],
            'payment_channel'   => ['nullable','string','max:255'],
            'payment_reference' => ['nullable','string','max:255'],
        ]);

        $createdAt = self::CREATED_AT_IS_UTC
            ? Carbon::parse($data['date'].' '.now()->format('H:i:s'), self::APP_TZ)->utc()
            : Carbon::parse($data['date'].' '.now()->format('H:i:s'));

        DB::table('cash_transactions')->insert([
            'user_id'           => auth()->id(),
            'type'              => 'out',
            'source'            => 'lain-lain',
            'amount'            => $data['amount'],
            'notes'             => $data['notes'] ?? null,
            'payment_channel'   => $data['payment_channel'] ?? null,
            'payment_reference' => $data['payment_reference'] ?? null,
            'created_at'        => $createdAt,
            'updated_at'        => now(),
        ]);

        return back()->with('ok', 'Pengeluaran lain-lain berhasil ditambahkan.');
    }

    /** =======================
     *  AJAX DATATABLES (Transaksi Terbaru)
     *  ======================= */
    public function cashReportData(Request $r)
    {
        $columns = ['created_at','type','source','amount','payment_channel','payment_reference','notes'];
        $length  = (int) $r->input('length', 50);
        $start   = (int) $r->input('start', 0);
        $draw    = (int) $r->input('draw', 1);

        $orderColIdx = (int) $r->input('order.0.column', 0);
        $orderDir    = strtolower($r->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $orderCol    = $columns[$orderColIdx] ?? 'created_at';

        $search      = trim((string) $r->input('search.value', ''));
        $from        = $r->input('from');
        $to          = $r->input('to');

        $qBase = DB::table('cash_transactions');
        self::applyDateFilter($qBase, $from, $to);

        $recordsTotal = (clone $qBase)->count();

        if ($search !== '') {
            $qBase->where(function($q) use ($search) {
                $q->where('source', 'like', "%{$search}%")
                  ->orWhere('payment_channel', 'like', "%{$search}%")
                  ->orWhere('payment_reference', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $qBase)->count();

        $rows = $qBase->orderBy($orderCol, $orderDir)
            ->offset($start)
            ->limit($length)
            ->get([
                'created_at','type','source','amount','payment_channel','payment_reference','notes'
            ])
            ->map(function($r){
                $isPin  = ($r->type === 'in' && $r->source === 'pin_purchase');
                $amount = (float)($r->amount ?? 0);

                $ratioBonus  = self::ALLOC_BONUS  / self::PIN_PRICE;
                $ratioProduk = self::ALLOC_PRODUK / self::PIN_PRICE;
                $ratioMgmt   = self::ALLOC_MGMT   / self::PIN_PRICE;

                $allocBonus  = $isPin ? round($amount * $ratioBonus, 2)  : 0.0;
                $allocProduk = $isPin ? round($amount * $ratioProduk, 2) : 0.0;
                $allocMgmt   = $isPin ? round($amount * $ratioMgmt, 2)   : 0.0;
                $pinQty      = $isPin && self::PIN_PRICE > 0 ? floor($amount / self::PIN_PRICE) : 0;

                return [
                    'created_at'        => (string) $r->created_at,
                    'type'              => (string) $r->type,
                    'source'            => (string) ($r->source ?? ''),
                    'amount'            => (float)  $amount,
                    'payment_channel'   => (string) ($r->payment_channel ?? ''),
                    'payment_reference' => (string) ($r->payment_reference ?? ''),
                    'notes'             => (string) ($r->notes ?? ''),
                    'pin_qty'           => $pinQty,
                    'alloc_bonus'       => $allocBonus,
                    'alloc_produk'      => $allocProduk,
                    'alloc_mgmt'        => $allocMgmt,
                ];
            });

        return response()->json([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $rows,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
