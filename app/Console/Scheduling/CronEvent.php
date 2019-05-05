<?php

namespace App\Console\Scheduling;

use App\Models\Instance\Cron;

class CronEvent
{
    /**
     * The cron model.
     *
     * @var Cron
     */
    private $cron;

    /**
     * Frequency to run the command.
     *
     * @var int
     */
    private $minutes;

    public function __construct(Cron $cron)
    {
        $this->cron = $cron;
    }
    
    /**
     * Get the command.
     *
     * @param string $command
     * @return self
     */
    public static function command(string $command) : CronEvent
    {
        $cron = Cron::firstOrCreate(['command' => $command]);

        return new CronEvent($cron);
    }

    /**
     * Run the command once per hour.
     *
     * @return self
     */
    public function hourly() : CronEvent
    {
        $this->minutes = 60;

        return $this;
    }

    /**
     * Run the command once per day.
     *
     * @return self
     */
    public function daily() : CronEvent
    {
        $this->minutes = 60 * 24;

        return $this;
    }

    /**
     * Test if the command is due to run.
     *
     * @return bool
     */
    public function isDue() : bool
    {
        $now  = now();

        if ($this->cron->last_run) {
            $next_run = $this->cron->last_run->addMinutes($this->minutes);

            if ($next_run > $now) {
                return false;
            }
        }

        $this->cron->update(['last_run' => $now]);

        return true;
    }
}
