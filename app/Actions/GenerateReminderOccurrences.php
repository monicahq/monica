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
 * Turn one rule into the concrete deadlines it produces between now and the far end of the rolling
 * window.
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
            return 0;
        }

        $this->generate();

        return $this->written;
    }

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
