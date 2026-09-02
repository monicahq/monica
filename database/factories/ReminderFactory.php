<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\ReminderMomentDirectionEnum;
use App\Enums\ReminderMomentUnitEnum;
use App\Enums\ReminderSourceEnum;
use App\Models\Contact;
use App\Models\FieldValue;
use App\Models\Reminder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends Factory<Reminder>
 */
class ReminderFactory extends Factory
{
    protected $model = Reminder::class;

    /**
     * Everything about where the rule lives is read off the value it reads,
     * since a rule in a different compartment than its own source is nothing.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function definition(): array
    {
        return [
            'field_value_id' => FieldValue::factory()->recurringDate(),
            'account_id' => fn (array $attributes): int => FieldValue::query()->findOrFail($attributes['field_value_id'])->account_id,
            'vault_id' => fn (array $attributes): int => FieldValue::query()->findOrFail($attributes['field_value_id'])->vault_id,
            'remindable_type' => Contact::class,
            'remindable_id' => fn (array $attributes): int => FieldValue::query()->findOrFail($attributes['field_value_id'])->valuable_id,
            'field_id' => fn (array $attributes): int => FieldValue::query()->findOrFail($attributes['field_value_id'])->field_id,
            'name' => 'Birthday — Michael Scott',
            'source' => ReminderSourceEnum::FieldValue,
            'cadence' => ReminderCadenceEnum::Yearly,
            'frequency_interval' => 1,
            'moments' => [
                [
                    'direction' => ReminderMomentDirectionEnum::Before->value,
                    'amount' => 7,
                    'unit' => ReminderMomentUnitEnum::Days->value,
                ],
                [
                    'direction' => ReminderMomentDirectionEnum::On->value,
                    'amount' => null,
                    'unit' => null,
                ],
            ],
            'time_of_day' => '09:00:00',
            'timezone' => 'Europe/Paris',
            'is_active' => true,
            'inactive_reason' => null,
            'deactivated_at' => null,
            'created_by_user_id' => null,
        ];
    }

    /**
     * Indicate that the rule fires once and is then done with.
     */
    public function once(): static
    {
        return $this->state(fn (array $attributes): array => [
            'cadence' => ReminderCadenceEnum::Once,
        ]);
    }

    /**
     * Indicate that the rule comes back on a rhythm somebody set themselves,
     * which is the yearly cadence with the interval spaced out.
     */
    public function everyFewYears(int $interval = 3): static
    {
        return $this->state(fn (array $attributes): array => [
            'cadence' => ReminderCadenceEnum::Yearly,
            'frequency_interval' => $interval,
        ]);
    }

    /**
     * Indicate that the rule has gone quiet, for the reason given.
     */
    public function inactive(ReminderInactiveReasonEnum $reason = ReminderInactiveReasonEnum::RecordArchived): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
            'inactive_reason' => $reason,
            'deactivated_at' => now(),
        ]);
    }
}
