<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReminderMomentDirectionEnum;
use App\Enums\ReminderMomentUnitEnum;
use App\Enums\ReminderOccurrenceCancelledReasonEnum;
use App\Enums\ReminderOccurrenceStatusEnum;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends Factory<ReminderOccurrence>
 */
class ReminderOccurrenceFactory extends Factory
{
    protected $model = ReminderOccurrence::class;

    /**
     * Where the deadline lives is read off the rule that produced it, since a
     * deadline in a different compartment than its own rule is nothing.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function definition(): array
    {
        $scheduledFor = now()->addDays(7)->startOfHour();

        return [
            'reminder_id' => Reminder::factory(),
            'account_id' => fn (array $attributes): int => Reminder::query()->findOrFail($attributes['reminder_id'])->account_id,
            'vault_id' => fn (array $attributes): int => Reminder::query()->findOrFail($attributes['reminder_id'])->vault_id,
            'source_local_date' => $scheduledFor->toDateString(),
            'moment_direction' => ReminderMomentDirectionEnum::On,
            'moment_amount' => null,
            'moment_unit' => null,
            'generation_key' => fn (array $attributes): string => '1:'.$attributes['source_local_date'].':on::',
            'scheduled_for' => $scheduledFor,
            'original_scheduled_for' => $scheduledFor,
            'local_date' => $scheduledFor->toDateString(),
            'status' => ReminderOccurrenceStatusEnum::Pending,
            'postponed_to' => null,
            'postponed_count' => 0,
            'notified_at' => null,
            'dismissed_at' => null,
            'cancelled_at' => null,
            'status_before_cancellation' => null,
            'cancelled_reason' => null,
        ];
    }

    /**
     * Indicate that the deadline came from a moment before the date rather than
     * from the day itself.
     */
    public function daysBefore(int $amount = 7): static
    {
        return $this->state(fn (array $attributes): array => [
            'moment_direction' => ReminderMomentDirectionEnum::Before,
            'moment_amount' => $amount,
            'moment_unit' => ReminderMomentUnitEnum::Days,
            'generation_key' => '1:'.$attributes['source_local_date'].':before:'.$amount.':days',
        ]);
    }

    /**
     * Indicate that the deadline has already fallen due, which is what every
     * test of delivery and of what may never be touched again needs.
     */
    public function past(): static
    {
        $scheduledFor = now()->subDays(3)->startOfHour();

        return $this->state(fn (array $attributes): array => [
            'scheduled_for' => $scheduledFor,
            'original_scheduled_for' => $scheduledFor,
            'local_date' => $scheduledFor->toDateString(),
            'source_local_date' => $scheduledFor->toDateString(),
        ]);
    }

    /**
     * Indicate that the deadline has been cancelled, for the reason given.
     */
    public function cancelled(
        ReminderOccurrenceCancelledReasonEnum $reason = ReminderOccurrenceCancelledReasonEnum::RecordArchived,
        ReminderOccurrenceStatusEnum $before = ReminderOccurrenceStatusEnum::Pending,
    ): static {
        return $this->state(fn (array $attributes): array => [
            'status' => ReminderOccurrenceStatusEnum::Cancelled,
            'cancelled_at' => now(),
            'cancelled_reason' => $reason,
            'status_before_cancellation' => $before,
        ]);
    }

    /**
     * Indicate that the first notification for the deadline has gone out.
     */
    public function notified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReminderOccurrenceStatusEnum::Notified,
            'notified_at' => now(),
        ]);
    }
}
