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
 */
class CancelReminderOccurrences
{
    public function __construct(
        private readonly Reminder $reminder,
        private readonly ReminderOccurrenceCancelledReasonEnum $reason,
    ) {}

    public function execute(): int
    {
        return DB::transaction(function (): int {
            $cancelled = 0;

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
