<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReminderOccurrenceCancelledReasonEnum;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use Carbon\CarbonImmutable;

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

        return ReminderOccurrence::query()
            ->where('reminder_id', $this->reminder->id)
            ->where('local_date', '>=', CarbonImmutable::now($this->reminder->timezone)->addDay()->toDateString())
            ->whereNull('answer')
            ->where('cancelled_reason', $this->reason)
            ->update([
                'cancelled_at' => null,
                'cancelled_reason' => null,
                'updated_at' => now(),
            ]);
    }
}
