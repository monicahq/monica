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
 */
class RestoreReminderOccurrences
{
    public function __construct(
        private readonly Reminder $reminder,
        private readonly ReminderOccurrenceCancelledReasonEnum $reason,
    ) {}

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
