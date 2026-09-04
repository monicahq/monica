<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReminderMomentDirectionEnum;
use App\Enums\ReminderMomentUnitEnum;
use App\Enums\ReminderOccurrenceStatusEnum;
use App\Helpers\ReminderSchedule;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Turn one rule into the concrete deadlines it produces between now and the far
 * end of the rolling window.
 *
 * It is written to be run again and again over the same window without doing
 * anything the second time. Every deadline carries a `generation_key` built from
 * the rule's schedule revision, the local date of the cycle and the normalised
 * moment, a unique index refuses a second row with the same key under the same
 * rule, and the write goes through `insertOrIgnore`, so two workers arriving at
 * the same instant produce one row between them rather than a duplicate or an
 * error somebody has to catch.
 *
 * The revision is what keeps that safe across a change. Cancelling a rule's
 * future deadlines and generating replacements gives the replacements new keys,
 * so nothing collides with what was just cancelled, and a rule changed and
 * changed back produces fresh rows rather than colliding with rows already
 * cancelled under the old schedule.
 *
 * Nothing is ever generated into the past. The window starts at the moment the
 * generator runs, so a rule made today over a date next week does not produce a
 * deadline for the moment seven days before it that has already gone: a deadline
 * that passed before its rule existed was never a deadline.
 *
 * A deadline somebody put off or dismissed is not regenerated either,
 * and for the same reason as everything else: its key is already taken.
 */
class GenerateReminderOccurrences
{
    private int $written = 0;

    public function __construct(
        private readonly Reminder $reminder,
        private readonly ?CarbonImmutable $until = null,
    ) {}

    /**
     * How many deadlines were written, which is zero on a rule that is quiet and
     * zero again on a second run over the same window.
     */
    public function execute(): int
    {
        if (! $this->produces()) {
            return 0;
        }

        $this->generate();

        return $this->written;
    }

    /**
     * Whether the rule still makes deadlines at all. A rule that has gone quiet,
     * one somebody took away, and one whose date has been cleared all make none,
     * and none of the three is an error.
     */
    private function produces(): bool
    {
        return $this->reminder->is_active
            && $this->reminder->deleted_at === null
            && $this->reminder->fieldValue?->value_date !== null;
    }

    private function generate(): void
    {
        $now = CarbonImmutable::now();
        $rows = [];

        $occurrences = ReminderSchedule::occurrencesBetween(
            reminder: $this->reminder,
            from: $now,
            to: $this->until ?? $now->addDays((int) config('monica.reminder_generation_window_in_days')),
        );

        foreach ($occurrences as $occurrence) {
            $rows[] = [
                'uuid' => (string) Str::uuid(),
                'account_id' => $this->reminder->account_id,
                'vault_id' => $this->reminder->vault_id,
                'reminder_id' => $this->reminder->id,
                'source_local_date' => $occurrence['source_local_date']->format('Y-m-d'),
                'moment_direction' => $occurrence['direction']->value,
                'moment_amount' => $occurrence['amount'],
                'moment_unit' => $occurrence['unit']?->value,
                'generation_key' => $this->keyFor($occurrence),
                'scheduled_for' => $occurrence['scheduled_for'],
                'original_scheduled_for' => $occurrence['scheduled_for'],
                'local_date' => $occurrence['local_date']->format('Y-m-d'),
                'status' => ReminderOccurrenceStatusEnum::Pending->value,
                'postponed_count' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            $this->written += ReminderOccurrence::query()->insertOrIgnore($chunk);
        }
    }

    /**
     * The identity of one generated deadline, readable in the table rather than
     * hashed, because the first thing anybody does with it is read it while
     * working out why a deadline is or is not there.
     *
     * @param  array{source_local_date: CarbonImmutable, direction: ReminderMomentDirectionEnum, amount: int|null, unit: ReminderMomentUnitEnum|null, local_date: CarbonImmutable, scheduled_for: CarbonImmutable}  $occurrence
     */
    private function keyFor(array $occurrence): string
    {
        return implode(':', [
            $this->reminder->schedule_revision,
            $occurrence['source_local_date']->format('Y-m-d'),
            $occurrence['direction']->value,
            $occurrence['amount'] ?? '',
            $occurrence['unit']->value ?? '',
        ]);
    }
}
