<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\BonusTransaction;
use App\Models\Withdrawal;
use App\Models\IncomeDetail;
use Carbon\Carbon;

class GenerateDailyIncomeReport extends Command
{
    protected $signature = 'report:daily-income';
    protected $description = 'Generate daily financial record for income_details table';

    public function handle()
{
    $startDate = \Carbon\Carbon::parse('2025-08-01');
    $endDate = \Carbon\Carbon::today();

    while ($startDate->lte($endDate)) {
        $tanggal = $startDate->toDateString();

        // Hitung jumlah user baru di tanggal itu
        $userCount = \App\Models\User::whereDate('created_at', $tanggal)->count();
        $pendaftaran = $userCount * 1000000;
        $produk = $userCount * 300000;
        $manajemen = $userCount * 200000;

        // Bonus pairing level 1–6 (uang 1 juta & produk)
        $pairing = BonusTransaction::whereDate('created_at', $tanggal)
            ->where('type', 'paid')
            ->sum('net_amount');

        // Bonus RO level 7+
        $p2 = BonusTransaction::whereDate('created_at', $tanggal)
            ->where('type', 'paid')
            ->where('notes', 'like', '%Bagan 2%')
            ->sum('net_amount');
        $p3 = BonusTransaction::whereDate('created_at', $tanggal)
            ->where('type', 'paid')
            ->where('notes', 'like', '%Bagan 3%')
            ->sum('net_amount');
        $p4 = BonusTransaction::whereDate('created_at', $tanggal)
            ->where('type', 'paid')
            ->where('notes', 'like', '%Bagan 4%')
            ->sum('net_amount');
        $p5 = BonusTransaction::whereDate('created_at', $tanggal)
            ->where('type', 'paid')
            ->where('notes', 'like', '%Bagan 5%')
            ->sum('net_amount');
        // Reward point (jika dicatat di type reward_point)
        $reward = BonusTransaction::whereDate('created_at', $tanggal)
            ->where('type', 'reward_point')
            ->sum('net_amount');

        // Withdraw yang sudah disetujui
        $withdraw = Withdrawal::whereNotNull('approved_at')
            ->whereDate('approved_at', $tanggal)
            ->sum('amount');

        // Simpan ke income_details
        IncomeDetail::updateOrCreate(
            ['date' => $tanggal],
            [
                'pendaftaran_member' => $pendaftaran,
                'produk' => $produk,
                'manajemen' => $manajemen,
                'pairing_bonus' => $pairing,
                'p2' => $p2,
                'p3' => $p3,
                'p4' => $p4,
                'p5' => $p5,
                'reward_poin' => $reward,
                'withdraw' => $withdraw,
            ]
        );

        $this->info("✅ Data keuangan tanggal $tanggal tersimpan.");
        $startDate->addDay();
    }

    $this->info('🎉 Selesai! Semua data income_details berhasil diisi.');
}

}
