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
        'App\Console\Commands\CalculateStatistics',
        'App\Console\Commands\Deactivate2FA',
        'App\Console\Commands\ExportAll',
        'App\Console\Commands\GetVersion',
        'App\Console\Commands\ImportCSV',
        'App\Console\Commands\ImportVCards',
        'App\Console\Commands\LangGenerate',
        'App\Console\Commands\PingVersionServer',
        'App\Console\Commands\SendNotifications',
        'App\Console\Commands\SendReminders',
        'App\Console\Commands\SendStayInTouch',
        'App\Console\Commands\SentryRelease',
        'App\Console\Commands\SetupProduction',
        'App\Console\Commands\SetupTest',
        'App\Console\Commands\SetupFrontEndTest',
        'App\Console\Commands\SetPremiumAccount',
        'App\Console\Commands\Update',
        'App\Console\Commands\MigrateDatabaseCollation',
        'App\Console\Commands\OneTime\MoveAvatars',
        'App\Console\Commands\Reminder\ProcessOldReminders',
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
        $schedule->command('send:stay_in_touch')->hourly();
        $schedule->command('monica:calculatestatistics')->daily();
        $schedule->command('process:old_reminders')->daily();
        $schedule->command('monica:ping')->daily();
        if (config('trustedproxy.cloudflare')) {
            $schedule->command('cloudflare:reload')->daily();
        }
    }
}
