<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\BonusManager;

class SimulatePairingBonus extends Command
{
    protected $signature = 'simulate:pairing {username}';
    protected $description = 'Simulasi proses pairing bonus untuk user berdasarkan username';

    public function handle()
    {
        $username = $this->argument('username');

        $user = User::where('username', $username)->first();

        if (!$user) {
            $this->error("User dengan username '{$username}' tidak ditemukan.");
            return 1;
        }

        $this->info("Menjalankan proses pairing untuk upline dari user: {$user->username}");
        BonusManager::process($user);

        $this->info("✅ Selesai proses pairing. Silakan cek log untuk detailnya.");
        return 0;
    }
}
