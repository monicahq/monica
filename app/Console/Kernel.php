<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // Commands\Inspire::class,
        'App\Console\Commands\SendNotifications',
        'App\Console\Commands\SendReminders',
        'App\Console\Commands\CalculateStatistics',
        'App\Console\Commands\ImportCSV',
        'App\Console\Commands\SetupProduction',
        'App\Console\Commands\ImportVCards',
        'App\Console\Commands\PingVersionServer',
        'App\Console\Commands\SetupTest',
        'App\Console\Commands\Deactivate2FA',
        'App\Console\Commands\GetVersion',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('send:notifications')->hourly();
        $schedule->command('send:reminders')->hourly();
        $schedule->command('monica:calculatestatistics')->daily();
        $schedule->command('monica:ping')->daily();
    }
}
