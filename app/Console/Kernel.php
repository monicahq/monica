<?php

namespace App\Console;

use App\Console\Scheduling\CronEvent;
use App\Domains\Contact\Dav\Jobs\CleanSyncToken;
use App\Domains\Contact\ManageReminders\Jobs\ProcessScheduledContactReminders;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
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

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     *
     * @codeCoverageIgnore
     */
    protected function schedule(Schedule $schedule)
    {
        $this->scheduleCommand($schedule, 'model:prune', 'daily');
        $this->scheduleCommand($schedule, 'queue:prune-batches', 'daily');
        $this->scheduleCommand($schedule, 'telescope:prune', 'daily');
        $this->scheduleJob($schedule, ProcessScheduledContactReminders::class, 'minutes', 1);
        $this->scheduleJob($schedule, CleanSyncToken::class, 'daily');
    }

    /**
     * Define a new schedule command with a frequency.
     *
     * @codeCoverageIgnore
     */
    private function scheduleCommand(Schedule $schedule, string $command, string $frequency, mixed ...$params)
    {
        $this->scheduleAction($schedule, $command, $frequency, $params);
    }

    /**
     * Define a new schedule command with a frequency.
     *
     * @codeCoverageIgnore
     */
    private function scheduleJob(Schedule $schedule, string $job, string $frequency, mixed ...$params)
    {
        $this->scheduleAction($schedule, $job, $frequency, $params, 'job');
    }

    /**
     * Define a new schedule.
     *
     * @codeCoverageIgnore
     */
    private function scheduleAction(Schedule $schedule, string $command, string $frequency, array $params, string $action = 'command')
    {
        $schedule->$action($command)->when(function () use ($command, $frequency, $params) {
            $event = CronEvent::command($command);
            if ($frequency) {
                $event = $event->{$frequency}(...$params);
            }

            return $event->isDue();
        });
    }
}
