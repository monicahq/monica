<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\GenerateReminderOccurrences;
use App\Models\Reminder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Expand the live rules whose rolling window has reached a deadline not written yet. Once a day is
 * enough, since the window is measured in months and all the frequency has to guarantee is that the
 * generator runs at least once inside it. A rule nobody has touched since its last pass waits here
 * until `generate_from` comes round, so the nightly cost follows the deadlines due rather than the
 * number of rules on the instance.
 */
class GenerateDueReminderOccurrences implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $rules = 0;
        $written = 0;

        Reminder::query()
            ->active()
            ->where('generate_from', '<=', now())
            ->with('fieldValue')
            ->chunkById(200, function ($reminders) use (&$rules, &$written): void {
                foreach ($reminders as $reminder) {
                    $rules++;
                    $written += new GenerateReminderOccurrences(reminder: $reminder)->execute();
                }
            });

        Log::info('Generated reminder deadlines.', ['rules' => $rules, 'occurrences' => $written]);
    }
}
