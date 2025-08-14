<?php

namespace App\Http\Controllers\Super;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\CashTransaction;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use App\Models\BonusTransaction;
use App\Models\Withdrawal;
use App\Models\MitraProfile;
use Illuminate\Support\Facades\DB;
class SuperWithdrawController extends Controller
{
    public function index()
    {
        $user = Auth::user();
         $bonus_sjm = BonusTransaction::whereIn('user_id', range(1, 15))->sum('amount');
        $bonus_manajemen = BonusTransaction::whereIn('user_id', range(16, 31))->sum('amount');
        $withdrawals = Withdrawal::whereIn('user_id', range(1, 31))->latest()->get();
         $mitraProfile = MitraProfile::where('user_id', $user->id)->first();
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
       
        $bonusAvailablesjm = $totalBonusSJMPaid  - $totalWithdrawn_sjm;
        $bonusAvailablemanajemen = $totalBonusManajemenPaid  - $totalWithdrawn_manajemen;


        return view('super_admin.index', compact('withdrawals', 'bonusAvailablesjm','bonusAvailablemanajemen', 'mitraProfile'));
    }
private function availableFor(int $userId): float
{
    $totalBonusPaid = (float) BonusTransaction::where('user_id', $userId)
        ->where('status', 'paid')
        ->sum('amount');

    $withdrawApproved = (float) Withdrawal::where('user_id', $userId)
        ->where('status', 'approved')
        ->sum('amount');

    $withdrawHeld = (float) Withdrawal::where('user_id', $userId)
        ->whereIn('status', ['pending','menunggu','processing'])
        ->sum('amount');

    return max(0, $totalBonusPaid - $withdrawApproved - $withdrawHeld);
}
    public function store(Request $request)
    {
        if (auth()->user()?->role === 'super-admin') {
        $request->validate([
            'only'         => 'nullable|in:all,sjm,manajemen', // default all
            'tax_percent'  => 'nullable|numeric|min:0|max:100',
            'min_amount'   => 'nullable|numeric|min:0',
            'channel'      => 'nullable|string',
            'details'      => 'nullable|string',
            'notes'        => 'nullable|string',
        ]);

        $scope      = $request->only ?? 'all';
        $taxPercent = (float)($request->tax_percent ?? 5);
        $minAmount  = (float)($request->min_amount ?? 50000);
        $channel    = $request->channel ?? 'bank_transfer';
        $details    = $request->details ?? '';
        $notes      = trim($request->notes ?? 'Auto-drain oleh Super Admin');

        $targets = [];
        if ($scope === 'all' || $scope === 'sjm')       foreach (range(1,15) as $id) $targets[] = ['id'=>$id,'group'=>'sjm'];
        if ($scope === 'all' || $scope === 'manajemen') foreach (range(16,31) as $id) $targets[] = ['id'=>$id,'group'=>'manajemen'];

        return DB::transaction(function () use ($targets, $taxPercent, $minAmount, $channel, $details, $notes) {
            $created = [];
            $sum = [
                'sjm' => ['count'=>0,'gross'=>0,'tax'=>0,'net'=>0],
                'manajemen' => ['count'=>0,'gross'=>0,'tax'=>0,'net'=>0],
            ];

            foreach ($targets as $t) {
                $uid = $t['id']; $group = $t['group'];

                // kunci ringan untuk mencegah lomba
                DB::table('withdrawals')->where('user_id', $uid)->sharedLock()->get();
                DB::table('bonus_transactions')->where('user_id', $uid)->sharedLock()->get();

                $available = $this->availableFor($uid);
                if ($available <= 0 || $available < $minAmount) continue;

                $amount = round($available, 2);                // drain semua saldo
                $tax    = round($amount * ($taxPercent/100),2);
                $net    = round($amount - $tax, 2);

                $w = Withdrawal::create([
                    'user_id'         => $uid,
                    'type'            => $group,      // tag grup
                    'amount'          => $amount,
                    'tax'             => $tax,
                    'net_amount'      => $net,        // jika kolomnya ada
                    'payment_channel' => $channel,
                    'payment_details' => $details,
                    'notes'           => $notes,
                    'status'          => 'pending',  // konsisten dengan Blade
                    'requested_at'    => now(),
                ]);

                $created[] = $w->id;
                $sum[$group]['count']++;
                $sum[$group]['gross'] += $amount;
                $sum[$group]['tax']   += $tax;
                $sum[$group]['net']   += $net;
            }

            $total = [
                'count' => $sum['sjm']['count'] + $sum['manajemen']['count'],
                'gross' => $sum['sjm']['gross'] + $sum['manajemen']['gross'],
                'tax'   => $sum['sjm']['tax']   + $sum['manajemen']['tax'],
                'net'   => $sum['sjm']['net']   + $sum['manajemen']['net'],
            ];

            return response()->json([
                'success' => true,
                'created_withdrawal_ids' => $created,
                'summary' => compact('sum','total'),
                'message' => $total['count']
                    ? 'Pencairan SJM/Manajemen berhasil diajukan (auto-drain).'
                    : 'Tidak ada saldo yang memenuhi minimum.',
            ]);
        });
    }

}
public function drainGroup(Request $request, string $group)
    {
        $request->validate([
            'tax_percent' => 'nullable|numeric|min:0|max:100',
            'min_amount'  => 'nullable|numeric|min:0',
            'notes'       => 'nullable|string',
            'channel'     => 'nullable|string',
            'details'     => 'nullable|string',
        ]);

        $taxPercent = (float)($request->tax_percent ?? 5);
        $minAmount  = (float)($request->min_amount ?? 50000);
        $notes      = trim($request->notes ?? "Auto-drain grup {$group} oleh Super Admin");
        $channel    = $request->channel ?? 'bank_transfer';
        $details    = $request->details ?? '';

        // Tentukan range ID sesuai grup
        if ($group === 'sjm') {
            $ids = range(1, 15);
        } elseif ($group === 'manajemen') {
            $ids = range(16, 31);
        } else {
            return response()->json(['success' => false, 'message' => 'Grup tidak dikenal'], 422);
        }

        return DB::transaction(function () use ($ids, $group, $taxPercent, $minAmount, $notes, $channel, $details) {
            $created = [];
            $sumGross = 0; $sumTax = 0; $sumNet = 0; $count = 0;

            foreach ($ids as $uid) {
                // Kunci ringan agar perhitungan tidak balapan saat ada proses lain
                DB::table('withdrawals')->where('user_id', $uid)->sharedLock()->get();
                DB::table('bonus_transactions')->where('user_id', $uid)->sharedLock()->get();

                $available = $this->availableFor($uid);
                if ($available <= 0 || $available < $minAmount) {
                    continue;
                }

                $amount = round($available, 2);                     // drain semua saldo
                $tax    = round($amount * ($taxPercent/100), 2);
                $net    = round($amount - $tax, 2);                 // hanya untuk ringkasan

                $w = Withdrawal::create([
                    'user_id'            => $uid,
                    'type'               => $group,     // tag: 'sjm' atau 'manajemen'
                    'amount'             => $amount,
                    'tax'                => $tax,
                    'status'             => 'pending', // konsisten dengan Blade
                    'transfer_reference' => null,
                    'processed_at'       => null,
                    'approved_at'        => null,
                    'admin_notes'        => $notes,
                    'payment_channel'    => $channel,   // pastikan kolom ini ada di tabelmu
                    'payment_details'    => $details,   // pastikan kolom ini ada di tabelmu
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);

                $created[]  = $w->id;
                $count     += 1;
                $sumGross  += $amount;
                $sumTax    += $tax;
                $sumNet    += $net;
            }

            return response()->json([
                'success' => true,
                'group'   => $group,
                'created_withdrawals' => $created,
                'summary' => [
                    'count' => $count,
                    'gross' => $sumGross,
                    'tax'   => $sumTax,
                    'net'   => $sumNet,
                ],
                'message' => $count
                    ? "Pencairan otomatis untuk grup {$group} berhasil diajukan."
                    : "Tidak ada user {$group} dengan saldo cukup untuk dicairkan.",
            ]);
        });
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
    $sjmIds = range(1, 15);
    $mngIds = range(16, 31);

    $sumPaid = function (array $ids) {
        return (float) BonusTransaction::whereIn('user_id', $ids)
            ->where('status', 'paid')
            ->sum('amount');
    };

    $sumWithdraw = function (array $ids, array $statuses) {
        return (float) Withdrawal::whereIn('user_id', $ids)
            ->whereIn('status', $statuses)
            ->sum('amount');
    };

    // Total bonus yang boleh dicairkan (paid)
    $sjmPaid = $sumPaid($sjmIds);
    $mngPaid = $sumPaid($mngIds);

    // Sudah cair
    $sjmApproved = $sumWithdraw($sjmIds, ['approved']);
    $mngApproved = $sumWithdraw($mngIds, ['approved']);

    // Masih menahan saldo (harus DIKURANGI)
    $sjmHeld = $sumWithdraw($sjmIds, ['pending', 'menunggu', 'processing']);
    $mngHeld = $sumWithdraw($mngIds, ['pending', 'menunggu', 'processing']);

    // Saldo tersedia = paid - approved - held
    $bonusAvailSjm = max(0, $sjmPaid - $sjmApproved - $sjmHeld);
    $bonusAvailMng = max(0, $mngPaid - $mngApproved - $mngHeld);

    return response()->json([
        'bonus_sjm'            => number_format($bonusAvailSjm, 0, ',', '.'),
        'bonus_raw_sjm'        => round($bonusAvailSjm, 2),
        'bonus_manajemen'      => number_format($bonusAvailMng, 0, ',', '.'),
        'bonus_raw_manajemen'  => round($bonusAvailMng, 2),
    ]);
}

}


