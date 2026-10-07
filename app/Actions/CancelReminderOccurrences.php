<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReminderOccurrenceCancelledReasonEnum;
use App\Models\Notification;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use Carbon\CarbonImmutable;

/**
 * Take away the deadlines of a rule still waiting for an answer, for the reason given: every one of
 * them when the rule or its contact goes away, or only those after today when the schedule changes.
 */
class CancelReminderOccurrences
{
    public function __construct(
        private readonly Reminder $reminder,
        private readonly ReminderOccurrenceCancelledReasonEnum $reason,
        private readonly bool $includePast,
    ) {}

    public function execute(): int
    {
        $query = ReminderOccurrence::query()
            ->where('reminder_id', $this->reminder->id)
            ->active();

        if (! $this->includePast) {
            $query->where('local_date', '>=', CarbonImmutable::now($this->reminder->timezone)->addDay()->toDateString());
        }

        $cancelled = $query->pluck('id');

        if ($cancelled->isEmpty()) {
            return 0;
        }

        Notification::query()
            ->where('notifiable_type', ReminderOccurrence::class)
            ->whereIn('notifiable_id', $cancelled)
            ->whereNull('sent_at')
            ->whereNull('failed_at')
            ->update([
                'failed_at' => now(),
                'failure_reason' => __('The reminder was cancelled.'),
                'updated_at' => now(),
            ]);

        return ReminderOccurrence::query()->whereIn('id', $cancelled)->update([
            'cancelled_at' => now(),
            'cancelled_reason' => $this->reason,
            'updated_at' => now(),
        ]);
    }
}
