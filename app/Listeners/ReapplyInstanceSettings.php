<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Helpers\InstanceSettings;
use Illuminate\Queue\Events\JobProcessing;

class ReapplyInstanceSettings
{
    /**
     * Re-read the settings before each queued job.
     *
     * A web request boots, reads and dies, so it always obeys the current
     * values. `queue:work` runs with no `--max-time` in the shipped stack, so a
     * worker boots once and would otherwise hold the configuration it started
     * with for as long as it lives.
     */
    public function handle(JobProcessing $event): void
    {
        InstanceSettings::apply();
    }
}
