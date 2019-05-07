<?php

namespace App\Console\Scheduling;

use Carbon\Carbon;
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
    private $minutes = 1;

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
    public static function command(string $command) : self
    {
        $cron = Cron::firstOrCreate(['command' => $command]);

        return new self($cron);
    }

    /**
     * Get current Cron.
     *
     * @return Cron
     */
    public function cron()
    {
        return $this->cron;
    }

    /**
     * Run the command once per hour.
     *
     * @return self
     */
    public function hourly() : self
    {
        $this->minutes = 60;

        return $this;
    }

    /**
     * Run the command once per day.
     *
     * @return self
     */
    public function daily() : self
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
        $now = now();

        if ($this->cron->last_run !== null) {
            $t = $this->cron->last_run;

            $next_run = Carbon::create($t->year, $t->month, $t->day, $t->hour, floor($t->minute / $this->minutes) * $this->minutes, 0)
                                ->addMinutes($this->minutes);

            if ($next_run > $now) {
                return false;
            }
        }

        $this->cron->update(['last_run' => $now]);

        return true;
    }
}
