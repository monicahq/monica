<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\ReminderSourceEnum;
use App\Models\Contact;
use App\Models\FieldValue;
use App\Models\Reminder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<Reminder> */
class ReminderFactory extends Factory
{
    protected $model = Reminder::class;

    /** @return array<string, mixed> */
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
            'starts_on' => null,
            'time_of_day' => '09:00:00',
            'timezone' => 'Europe/Paris',
            'schedule_revision' => 1,
            'is_active' => true,
            'inactive_reason' => null,
            'deactivated_at' => null,
            'created_by_user_id' => null,
        ];
    }

    public function manual(Contact $contact, string $startsOn): static
    {
        return $this->state(fn (array $attributes): array => [
            'field_value_id' => null,
            'field_id' => null,
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'remindable_type' => Contact::class,
            'remindable_id' => $contact->id,
            'name' => 'Call about the flat on Bedford Street',
            'source' => ReminderSourceEnum::Manual,
            'starts_on' => $startsOn,
        ]);
    }

    public function once(): static
    {
        return $this->state(fn (array $attributes): array => [
            'cadence' => ReminderCadenceEnum::Once,
        ]);
    }

    public function everyFewYears(int $interval = 3): static
    {
        return $this->state(fn (array $attributes): array => [
            'cadence' => ReminderCadenceEnum::Yearly,
            'frequency_interval' => $interval,
        ]);
    }

    public function inactive(ReminderInactiveReasonEnum $reason = ReminderInactiveReasonEnum::RecordArchived): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
            'inactive_reason' => $reason,
            'deactivated_at' => now(),
        ]);
    }
}
