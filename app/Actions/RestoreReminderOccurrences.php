<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReminderOccurrenceCancelledReasonEnum;
use App\Enums\ReminderOccurrenceStatusEnum;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use Illuminate\Support\Facades\DB;

/**
 * Put back the deadlines one cause took away, now that the cause is reversed.
 *
 * The reason is the whole point. Unarchiving a contact reverses archiving and
 * nothing else, so a deadline cancelled because the field it read left the
 * structure stays cancelled and goes on saying so. Reviving everything would be
 * undoing something nobody undid.
 *
 * Three of the seven reasons are never reversed, and deliberately. When the date
 * or the schedule changed, the replacement deadlines are the ones the new
 * schedule defines, and bringing the old ones back would fire reminders for a
 * date nobody holds any more. Clearing the value is the same case, since typing
 * the date in again makes a new rule with deadlines of its own.
 *
 * Only what is still ahead comes back. A deadline whose instant passed while the
 * contact sat in the archive is not resurrected into the present: it was
 * cancelled, it stayed cancelled, and that is what happened.
 *
 * Each row returns to the state it held before, which is why that state was kept
 * rather than assumed to be pending.
 */
class RestoreReminderOccurrences
{
    public function __construct(
        private readonly Reminder $reminder,
        private readonly ReminderOccurrenceCancelledReasonEnum $reason,
    ) {}

    /**
     * How many deadlines came back.
     */
    public function execute(): int
    {
        if (! $this->reason->isReversible()) {
            return 0;
        }

        return DB::transaction(function (): int {
            $restored = 0;

            foreach (ReminderOccurrenceStatusEnum::actionable() as $status) {
                $restored += ReminderOccurrence::query()
                    ->where('reminder_id', $this->reminder->id)
                    ->stillAhead()
                    ->where('status', ReminderOccurrenceStatusEnum::Cancelled)
                    ->where('cancelled_reason', $this->reason)
                    ->where('status_before_cancellation', $status)
                    ->update([
                        'status' => $status,
                        'status_before_cancellation' => null,
                        'cancelled_at' => null,
                        'cancelled_reason' => null,
                        'updated_at' => now(),
                    ]);
            }

            return $restored;
        });
    }
}
