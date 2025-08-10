<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PreRegistration;
use App\Models\CashTransaction;
use App\Models\User;

class SyncPreRegistrationsToCashTransactions extends Command
{
    protected $signature = 'sync:pre-registrations-cash';
    protected $description = 'Sinkronisasi pre-registrations ke cash_transactions untuk kas masuk';

    public function handle()
    {
        $registrations = PreRegistration::where('status', 'approved')->get();
        $count = 0;

        foreach ($registrations as $pre) {
            // Cek apakah transaksi ini sudah dicatat
            $already = CashTransaction::where('source', 'registration')
                ->where('payment_reference', $pre->payment_proof)
                ->exists();

            if ($already) {
                continue;
            }

            // Cari user jika sudah terdaftar
            $user = User::where('email', $pre->email)->first();

            CashTransaction::create([
                'user_id' => $user->id ?? null,
                'type' => 'in',
                'source' => 'registration',
                'amount' => 1500000,
                'notes' => 'Dari pre-registration: ' . $pre->name,
                'payment_channel' => $pre->payment_method,
                'payment_reference' => $pre->payment_proof,
            ]);

            $count++;
        }

        $this->info("Sinkronisasi selesai. $count transaksi baru berhasil dicatat.");
    }
}