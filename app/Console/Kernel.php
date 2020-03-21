<?php

namespace App\Console;

use App\Console\Commands\Clean;
use App\Console\Commands\Update;
use App\Console\Commands\ExportAll;
use App\Console\Commands\ImportCSV;
use App\Console\Commands\SetupTest;
use App\Console\Commands\GetVersion;
use App\Console\Scheduling\CronEvent;
use App\Console\Commands\ImportVCards;
use App\Console\Commands\LangGenerate;
use App\Console\Commands\SetUserAdmin;
use App\Console\Commands\Deactivate2FA;
use App\Console\Commands\SendReminders;
use App\Console\Commands\SentryRelease;
use App\Console\Commands\SendStayInTouch;
use App\Console\Commands\SetupProduction;
use App\Console\Commands\UpdateGravatars;
use App\Console\Commands\PingVersionServer;
use App\Console\Commands\SetPremiumAccount;
use Illuminate\Console\Scheduling\Schedule;
use App\Console\Commands\CalculateStatistics;
use App\Console\Commands\OneTime\MoveAvatars;
use App\Console\Commands\MigrateDatabaseCollation;
use App\Console\Commands\Tests\SetupFrontEndTestUser;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\OneTime\MoveAvatarsToPhotosDirectory;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        CalculateStatistics::class,
        Clean::class,
        Deactivate2FA::class,
        ExportAll::class,
        GetVersion::class,
        ImportCSV::class,
        ImportVCards::class,
        LangGenerate::class,
        MigrateDatabaseCollation::class,
        MoveAvatars::class,
        MoveAvatarsToPhotosDirectory::class,
        PingVersionServer::class,
        SendReminders::class,
        SendStayInTouch::class,
        SentryRelease::class,
        SetPremiumAccount::class,
        SetupProduction::class,
        SetupTest::class,
        SetUserAdmin::class,
        Update::class,
        UpdateGravatars::class,
    ];

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        if ($this->app->environment() != 'production') {
            $this->commands[] = SetupFrontEndTestUser::class;
        }
    }

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $this->scheduleCommand($schedule, 'send:reminders', 'hourly');
        $this->scheduleCommand($schedule, 'send:stay_in_touch', 'hourly');
        $this->scheduleCommand($schedule, 'monica:calculatestatistics', 'daily');
        $this->scheduleCommand($schedule, 'monica:ping', 'daily');
        $this->scheduleCommand($schedule, 'monica:clean', 'daily');
        $this->scheduleCommand($schedule, 'monica:updategravatars', 'weekly');
        if (config('trustedproxy.cloudflare')) {
            $this->scheduleCommand($schedule, 'cloudflare:reload', 'daily'); // @codeCoverageIgnore
        }
    }

    /**
     * Define a new schedule command with a frequency.
     */
    private function scheduleCommand(Schedule $schedule, string $command, $frequency)
    {
        $schedule->command($command)->when(function () use ($command, $frequency) {
            $event = CronEvent::command($command); // @codeCoverageIgnore
            if ($frequency) { // @codeCoverageIgnore
                $event = $event->$frequency(); // @codeCoverageIgnore
            }

            return $event->isDue(); // @codeCoverageIgnore
        });
    }
}
