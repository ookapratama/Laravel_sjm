<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\ActivationPin;
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
    $startDate = \Carbon\Carbon::parse('2025-08-10');
    $endDate = \Carbon\Carbon::today();

    while ($startDate->lte($endDate)) {
        $tanggal = $startDate->toDateString();

        // Hitung jumlah user baru di tanggal itu
        $userCount = ActivationPin::whereDate('created_at', $tanggal)->count();
        $pendaftaran = $userCount * 500000;
        $produk = $userCount * 150000;
        $manajemen = $userCount * 100000;

        // Bonus pairing level 1–6 (uang 1 juta & produk)
        $pairing = BonusTransaction::whereDate('created_at', $tanggal)
            ->where('status', 'paid')
            ->sum('net_amount');

        // Bonus RO level 7+
        $p1 = BonusTransaction::whereDate('created_at', $tanggal)
            ->where('status', 'paid')
            ->where('notes', 'like', '%Bagan 1%')
            ->sum('net_amount');
        $p2 = BonusTransaction::whereDate('created_at', $tanggal)
            ->where('status', 'paid')
            ->where('notes', 'like', '%Bagan 2%')
            ->sum('net_amount');
        $p3 = BonusTransaction::whereDate('created_at', $tanggal)
            ->where('status', 'paid')
            ->where('notes', 'like', '%Bagan 3%')
            ->sum('net_amount');
        $p4 = BonusTransaction::whereDate('created_at', $tanggal)
            ->where('status', 'paid')
            ->where('notes', 'like', '%Bagan 4%')
            ->sum('net_amount');
        $p5 = BonusTransaction::whereDate('created_at', $tanggal)
            ->where('status', 'paid')
            ->where('notes', 'like', '%Bagan 5%')
            ->sum('net_amount');
        $p6 = BonusTransaction::whereDate('created_at', $tanggal)
            ->where('status', 'paid')
            ->where('notes', 'like', '%Bagan 6%')
            ->sum('net_amount');
        // Reward point (jika dicatat di type reward_point)

        // Withdraw yang sudah disetujui
        $withdraw = Withdrawal::whereNotNull('processed_at')
            ->whereDate('processed_at', $tanggal)
            ->sum('amount');

        // Simpan ke income_details
        IncomeDetail::updateOrCreate(
            ['date' => $tanggal],
            [
                'penjualan_pin' => $pendaftaran,
                'produk' => $produk,
                'manajemen' => $manajemen,
                'pairing_bonus' => $pairing,
                'p1' => $p1,
                'p2' => $p2,
                'p3' => $p3,
                'p4' => $p4,
                'p5' => $p5,
                'p6' => $p6,
       
                'withdraw' => $withdraw,
            ]
        );

        $this->info("✅ Data keuangan tanggal $tanggal tersimpan.");
        $startDate->addDay();
    }

    $this->info('🎉 Selesai! Semua data income_details berhasil diisi.');
}

}
