<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\GenerateReminderOccurrences;
use App\Models\Reminder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Expand every live rule into the deadlines it produces inside the rolling
 * window.
 *
 * Once a day is often enough. The window is measured in months rather than
 * hours, so a deadline three days from now was written weeks ago and a sweep
 * running every minute would find the same nothing over and over. What the
 * frequency has to guarantee is only this: the generator must run at least once
 * inside the window, or a deadline that fell entirely inside the gap was never
 * written and is not invented afterwards.
 *
 * It holds no rules of its own. Which deadlines a rule produces, and what makes
 * generating twice harmless, both live in {@see GenerateReminderOccurrences},
 * which the actions that change a rule call directly.
 *
 * The count is logged rather than discarded, so a run that produced nothing can
 * be told apart from a run that never happened.
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
