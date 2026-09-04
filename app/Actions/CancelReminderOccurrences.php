<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReminderOccurrenceCancelledReasonEnum;
use App\Enums\ReminderOccurrenceStatusEnum;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use Illuminate\Support\Facades\DB;

/**
 * Take away the deadlines a rule has ahead of it, for the reason given.
 *
 * Only what is ahead. A deadline whose instant has passed is history, and
 * history is not edited: whether somebody was told about their friend's birthday
 * last March does not change because the date was corrected this week.
 *
 * Nothing is deleted. The row stays, says which of the seven things happened to
 * it, and remembers the state it was in beforehand so that reversing the cause
 * puts it back exactly as it was rather than guessing at pending.
 *
 * A deadline somebody has already dismissed is left alone. They dealt with it,
 * and overwriting that with a cancellation would be the product throwing away
 * what somebody did.
 */
class CancelReminderOccurrences
{
    public function __construct(
        private readonly Reminder $reminder,
        private readonly ReminderOccurrenceCancelledReasonEnum $reason,
    ) {}

    /**
     * How many deadlines were taken away.
     */
    public function execute(): int
    {
        return DB::transaction(function (): int {
            $cancelled = 0;

            /* One statement per state it could have been in, rather than one
               statement copying the column onto itself. `SET status = 'x',
               previous = status` reads the new value on MySQL and the old one on
               PostgreSQL and SQLite, and the suite has to pass on all three. */
            foreach (ReminderOccurrenceStatusEnum::actionable() as $status) {
                $cancelled += ReminderOccurrence::query()
                    ->where('reminder_id', $this->reminder->id)
                    ->stillAhead()
                    ->where('status', $status)
                    ->update([
                        'status' => ReminderOccurrenceStatusEnum::Cancelled,
                        'status_before_cancellation' => $status,
                        'cancelled_at' => now(),
                        'cancelled_reason' => $this->reason,
                        'updated_at' => now(),
                    ]);
            }

            return $cancelled;
        });
    }
}
