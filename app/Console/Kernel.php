<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

// Tambahkan use ini:
use App\Console\Commands\GenerateDailyIncomeReport;

class Kernel extends ConsoleKernel
{
    /**
     * Register command class secara manual
     */
    protected $commands = [
        GenerateDailyIncomeReport::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('sync:pre-registrations-cash');
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
