<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\GenerateReminderOccurrences;
use App\Models\Reminder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Expand every live rule into the deadlines it produces inside the rolling window. Once a day is
 * enough, since the window is measured in months and all the frequency has to guarantee is that the
 * generator runs at least once inside it.
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
