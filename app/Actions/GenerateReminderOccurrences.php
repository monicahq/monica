<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReminderSourceEnum;
use App\Helpers\ReminderSchedule;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Turn one rule into the concrete deadlines it produces between now and the far end of the rolling
 * window, and note when that window will reach the next deadline, so the nightly pass leaves the rule
 * alone until then.
 */
class GenerateReminderOccurrences
{
    private int $written = 0;

    public function __construct(
        private readonly Reminder $reminder,
        private readonly ?CarbonImmutable $until = null,
    ) {}

    public function execute(): int
    {
        if (! $this->produces()) {
            $this->reminder->update(['generate_from' => null]);

            return 0;
        }

        $this->generate();

        return $this->written;
    }

    private function produces(): bool
    {
        if (! $this->reminder->is_active || $this->reminder->deleted_at !== null) {
            return false;
        }

        return $this->reminder->source === ReminderSourceEnum::Manual
            ? $this->reminder->starts_on !== null
            : $this->reminder->fieldValue?->value_date !== null;
    }

    private function generate(): void
    {
        $now = CarbonImmutable::now();
        $window = (int) config('monica.reminder_generation_window_in_days');
        $until = $this->until ?? $now->addDays($window);
        $rows = [];

        $occurrences = ReminderSchedule::occurrencesBetween(
            reminder: $this->reminder,
            from: $now,
            to: $until,
        );

        $taken = ReminderOccurrence::query()
            ->where('reminder_id', $this->reminder->id)
            ->whereNull('cancelled_at')
            ->whereDate('local_date', '>=', $now->setTimezone($this->reminder->timezone)->toDateString())
            ->get(['local_date'])
            ->map(fn (ReminderOccurrence $taken): string => $taken->local_date->format('Y-m-d'))
            ->all();

        foreach ($occurrences as $occurrence) {
            if (in_array($occurrence['local_date']->format('Y-m-d'), $taken, true)) {
                continue;
            }

            $rows[] = [
                'uuid' => (string) Str::uuid(),
                'account_id' => $this->reminder->account_id,
                'vault_id' => $this->reminder->vault_id,
                'reminder_id' => $this->reminder->id,
                'local_date' => $occurrence['local_date']->format('Y-m-d'),
                'generation_key' => $this->reminder->schedule_revision.':'.$occurrence['local_date']->format('Y-m-d'),
                'scheduled_for' => $occurrence['scheduled_for'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            $this->written += ReminderOccurrence::query()->insertOrIgnore($chunk);
        }

        $this->reminder->update(['generate_from' => $this->firstDeadlineAfter($until)?->subDays($window)]);
    }

    private function firstDeadlineAfter(CarbonImmutable $until): ?CarbonImmutable
    {
        $day = $until->setTimezone($this->reminder->timezone)->startOfDay();
        $date = ReminderSchedule::nextDate(reminder: $this->reminder, from: $day);

        if ($date !== null && ReminderSchedule::instantOf(reminder: $this->reminder, localDate: $date)->lessThanOrEqualTo($until)) {
            $date = ReminderSchedule::nextDate(reminder: $this->reminder, from: $day->addDay());
        }

        if ($date === null) {
            return null;
        }

        return ReminderSchedule::instantOf(reminder: $this->reminder, localDate: $date);
    }
}
