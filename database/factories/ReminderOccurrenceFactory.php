<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReminderAnswerEnum;
use App\Enums\ReminderOccurrenceCancelledReasonEnum;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<ReminderOccurrence> */
class ReminderOccurrenceFactory extends Factory
{
    protected $model = ReminderOccurrence::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        $scheduledFor = now()->addDays(7)->startOfHour();

        return [
            'reminder_id' => Reminder::factory(),
            'account_id' => fn (array $attributes): int => Reminder::query()->findOrFail($attributes['reminder_id'])->account_id,
            'vault_id' => fn (array $attributes): int => Reminder::query()->findOrFail($attributes['reminder_id'])->vault_id,
            'local_date' => $scheduledFor->toDateString(),
            'generation_key' => fn (array $attributes): string => '1:'.$attributes['local_date'],
            'scheduled_for' => $scheduledFor,
            'notifications_created_at' => null,
            'notified_at' => null,
            'answer' => null,
            'answered_at' => null,
            'cancelled_at' => null,
            'cancelled_reason' => null,
        ];
    }

    public function past(): static
    {
        return $this->daysFromNow(-3);
    }

    public function today(): static
    {
        return $this->daysFromNow(0);
    }

    public function daysFromNow(int $days): static
    {
        $scheduledFor = now()->addDays($days)->startOfHour();

        return $this->state(fn (array $attributes): array => [
            'scheduled_for' => $scheduledFor,
            'local_date' => $scheduledFor->toDateString(),
            'generation_key' => '1:'.$scheduledFor->toDateString(),
        ]);
    }

    public function cancelled(ReminderOccurrenceCancelledReasonEnum $reason = ReminderOccurrenceCancelledReasonEnum::RecordArchived): static
    {
        return $this->state(fn (array $attributes): array => [
            'cancelled_at' => now(),
            'cancelled_reason' => $reason,
        ]);
    }

    public function notified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'notifications_created_at' => now(),
            'notified_at' => now(),
        ]);
    }

    public function remembered(): static
    {
        return $this->state(fn (array $attributes): array => [
            'answer' => ReminderAnswerEnum::Remembered,
            'answered_at' => now(),
        ]);
    }

    public function forgotten(): static
    {
        return $this->state(fn (array $attributes): array => [
            'answer' => ReminderAnswerEnum::Forgotten,
            'answered_at' => now(),
        ]);
    }
}
