<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\BonusManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
class ProcessPairingJob implements ShouldQueue
{
    
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;

    /**
     * Buat Job baru.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Jalankan Job.
     */
public function handle()
{
    \Log::info("🎯 Job pairing dijalankan untuk user {$this->user->username}");

    try {
        (new \App\Services\BonusManager)->process($this->user);
    } catch (\Throwable $e) {
        \Log::error("❌ Gagal jalankan job pairing: " . $e->getMessage());
    }
}


}
