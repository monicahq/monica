<?php

namespace App\Console;

use App\Console\Scheduling\CronEvent;
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
    ];

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        $this->load(__DIR__.'/Commands/OneTime');

        if ($this->app->environment() != 'production') {
            $this->load(__DIR__.'/Commands/Tests');
        }

        require base_path('routes/console.php');
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
