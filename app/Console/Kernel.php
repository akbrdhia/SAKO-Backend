<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        // Cek cicilan telat - setiap hari jam 00:00
        $schedule->command('cicilan:check-telat --time=00:00')
            ->daily()
            ->at('00:00')
            ->withoutOverlapping()
            ->onOneServer();

        // Kirim reminder cicilan H-3 - setiap hari jam 08:00
        $schedule->command('cicilan:send-reminder --days=3 --time=08:00')
            ->daily()
            ->at('08:00')
            ->withoutOverlapping()
            ->onOneServer();

        // Alternative: Bisa juga pakai configurable time dari config
        // $schedule->command('cicilan:check-telat --time=' . config('cicilan.check_telat_waktu'))
        //     ->daily()
        //     ->at(config('cicilan.check_telat_waktu'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
