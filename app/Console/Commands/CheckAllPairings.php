<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\BonusManager;

class CheckAllPairings extends Command
{
    protected $signature = 'mlm:check-pairings';
    protected $description = 'Looping semua user untuk mengecek dan menjalankan pairing jika syarat terpenuhi';

    public function handle()
    {
        $this->info("🔍 Mengecek semua user yang berpotensi pairing...");

        $users = User::whereHas('children', function ($q) {
            $q->whereIn('position', ['left', 'right']);
        })->get();

        $total = 0;

        foreach ($users as $user) {
            $left = $user->getLeftChild();
            $right = $user->getRightChild();

            if ($left && $right) {
                $this->info("➡️ Memproses pairing untuk: {$user->username} (ID {$user->id})");
                BonusManager::process($user);
                $total++;
            }
        }

        $this->info("✅ Total user yang diproses pairing: $total");
        return 0;
    }
}
