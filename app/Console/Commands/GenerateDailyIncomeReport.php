<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ActivationPin;
use App\Models\BonusTransaction;
use App\Models\Withdrawal;
use App\Models\IncomeDetail;
use Carbon\Carbon;
use DB;

class GenerateDailyIncomeReport extends Command
{
    protected $signature = 'report:daily';
    protected $description = 'Generate daily financial record for income_details table';

    public function handle()
    {
        $tz = 'Asia/Makassar';

        // DEBUG: pastikan CLI pakai DB & timezone yang kamu kira
        $this->info('DB: '.config('database.connections.'.config('database.default').'.database'));
        $this->info('APP_ENV: '.config('app.env'));
        $this->info('Now WITA: '.Carbon::now($tz)->toDateTimeString());

        // Pakai WITA untuk rentang hari
        $startDate = Carbon::parse('2025-08-10', $tz)->startOfDay();
        $endDate   = Carbon::today($tz);

        while ($startDate->lte($endDate)) {
            $tanggal = $startDate->toDateString(); // YYYY-MM-DD (WITA)

            // ==== PENJUALAN PIN (sesuai permintaanmu: whereDate(created_at))
            // Sanity check via Query Builder (bypass model) — akan terlihat kalau model/connection beda
            $rawCount  = DB::table('activation_pins')->whereDate('created_at', $tanggal)->count();
            $userCount = ActivationPin::whereDate('created_at', $tanggal)->count();

            // Hitung nilai
            $penjualan_pin = $userCount * 500000;
            $produk        = $userCount * 150000;
            $manajemen     = $userCount * 100000;

            // ==== BONUS, WITHDRAW, DLL (biarkan seperti sebelumnya)
            $pairing = BonusTransaction::whereDate('created_at', $tanggal)
                        ->where('status', 'paid')
                        ->sum('net_amount');

            $p1 = BonusTransaction::whereDate('created_at', $tanggal)->where('status','paid')->where('notes','like','%Bagan 1%')->sum('net_amount');
            $p2 = BonusTransaction::whereDate('created_at', $tanggal)->where('status','paid')->where('notes','like','%Bagan 2%')->sum('net_amount');
            $p3 = BonusTransaction::whereDate('created_at', $tanggal)->where('status','paid')->where('notes','like','%Bagan 3%')->sum('net_amount');
            $p4 = BonusTransaction::whereDate('created_at', $tanggal)->where('status','paid')->where('notes','like','%Bagan 4%')->sum('net_amount');
            $p5 = BonusTransaction::whereDate('created_at', $tanggal)->where('status','paid')->where('notes','like','%Bagan 5%')->sum('net_amount');
            $p6 = BonusTransaction::whereDate('created_at', $tanggal)->where('status','paid')->where('notes','like','%Bagan 6%')->sum('net_amount');

            $withdraw = Withdrawal::whereNotNull('processed_at')
                        ->whereDate('processed_at', $tanggal)
                        ->sum('amount');

            // Simpan
            IncomeDetail::updateOrCreate(
                ['date' => $tanggal],
                [
                    'penjualan_pin' => $penjualan_pin,
                    'produk'        => $produk,
                    'manajemen'     => $manajemen,
                    'pairing_bonus' => $pairing,
                    'p1' => $p1, 'p2' => $p2, 'p3' => $p3, 'p4' => $p4, 'p5' => $p5, 'p6' => $p6,
                    'withdraw'      => $withdraw,
                ]
            );

            // DEBUG log supaya kelihatan jelas
            $this->info("[$tanggal] rawCount=$rawCount eloquentCount=$userCount -> penjualan_pin=".number_format($penjualan_pin));

            $startDate->addDay();
        }

        $this->info('🎉 Selesai! Semua data income_details berhasil diisi.');
    }
}
