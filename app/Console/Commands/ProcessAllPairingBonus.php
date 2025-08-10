<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\BonusManager;

class ProcessAllPairingBonus extends Command
{
    protected $signature = 'bonus:process';
    protected $description = 'Proses bonus pairing semua user dari bawah ke atas';

    public function handle()
    {
        $this->info('🔁 Mulai proses bonus pairing semua user...');

        $users = User::orderByDesc('id')->get();

        $total = 0;

        foreach ($users as $user) {
            $left = $user->getLeftChild();
            $right = $user->getRightChild();

            if ($left && $right) {
                $this->info("➡️ Proses: {$user->username} (ID: {$user->id})");
                (new BonusManager)->process($user); // ✅ perbaikan di sini
                $total++;
            } else {
                $this->line("⏩ Lewati: {$user->username} (ID: {$user->id}) - Tidak punya anak kiri & kanan");
            }
        }

        $this->info("✅ Selesai. Total user yang diproses pairing: $total");
        return 0;
    }
}
