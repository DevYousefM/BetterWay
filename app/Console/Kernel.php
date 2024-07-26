<?php

namespace App\Console;

use App\Console\Commands\GetUsersRegistered14DaysAgo;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use  App\Jobs\EventEnd;
use  App\Jobs\BonanzaEnd;
use  App\Jobs\ChequeCycle;
use  App\Jobs\ProductRenewal;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->job(new BonanzaEnd)->everyMinute();
        $schedule->job(new EventEnd)->hourly();
        $schedule->job(new BonanzaEnd)->daily();
        $schedule->job(new ChequeCycle)->everyMinute();
        $schedule->job(new ProductRenewal)->daily();
        $schedule->command('client:get-registered-14-days-ago')->everyMinute();
        $schedule->command('send:notifications')->daily();
        $schedule->command('clients:positions')->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
