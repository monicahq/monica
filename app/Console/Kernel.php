<?php

namespace App\Console;

use App\Domains\Contact\ManageReminders\Jobs\ProcessScheduledContactReminders;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     * @codeCoverageIgnore
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->job(new ProcessScheduledContactReminders())->everyMinute();
        $schedule->command('telescope:prune')->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
