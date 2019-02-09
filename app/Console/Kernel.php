<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\OneTime\MoveAvatars;
use App\Console\Commands\MigrateDatabaseCollation;
use App\Console\Commands\Update;
use App\Console\Commands\SetUserAdmin;
use App\Console\Commands\SetPremiumAccount;
use App\Console\Commands\SetupFrontEndTest;
use App\Console\Commands\SetupTest;
use App\Console\Commands\SetupProduction;
use App\Console\Commands\SentryRelease;
use App\Console\Commands\SendStayInTouch;
use App\Console\Commands\SendReminders;
use App\Console\Commands\PingVersionServer;
use App\Console\Commands\LangGenerate;
use App\Console\Commands\ImportVCards;
use App\Console\Commands\ImportCSV;
use App\Console\Commands\GetVersion;
use App\Console\Commands\ExportAll;
use App\Console\Commands\Deactivate2FA;
use App\Console\Commands\CalculateStatistics;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        CalculateStatistics::class,
        Deactivate2FA::class,
        ExportAll::class,
        GetVersion::class,
        ImportCSV::class,
        ImportVCards::class,
        LangGenerate::class,
        PingVersionServer::class,
        SendReminders::class,
        SendStayInTouch::class,
        SentryRelease::class,
        SetupProduction::class,
        SetupTest::class,
        SetupFrontEndTest::class,
        SetPremiumAccount::class,
        SetUserAdmin::class,
        Update::class,
        MigrateDatabaseCollation::class,
        MoveAvatars::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('send:reminders')->hourly();
        $schedule->command('send:stay_in_touch')->hourly();
        $schedule->command('monica:calculatestatistics')->daily();
        $schedule->command('monica:ping')->daily();
        if (config('trustedproxy.cloudflare')) {
            $schedule->command('cloudflare:reload')->daily();
        }
        if (config('telescope.enabled')) {
            $schedule->command('telescope:prune --hours=48')->daily();
        }
    }
}
