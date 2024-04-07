<?php

namespace App\Console\Scheduling;

use Illuminate\Support\Facades\Schedule as ScheduleFacade;

/**
 * @method static void command(string $command, string $frequency = null)
 * @method static void job(string $command, string $frequency = null, int $args = 0)
 */
class Schedule
{
    /**
     * Handle dynamic, static calls to the object.
     */
    public static function __callStatic(string $method, array $args): void
    {
        $command = array_shift($args);
        $frequency = array_shift($args);
        ScheduleFacade::$method($command)->when(function () use ($command, $frequency, $args) {
            $event = CronEvent::command($command);
            if ($frequency !== null) {
                $event = $event->{$frequency}(...$args);
            }

            return $event->isDue();
        });
    }
}
