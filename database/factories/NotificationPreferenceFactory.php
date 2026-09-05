<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NotificationDeliveryModeEnum;
use App\Models\Account;
use App\Models\NotificationPreference;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<NotificationPreference> */
class NotificationPreferenceFactory extends Factory
{
    protected $model = NotificationPreference::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'in_app_is_enabled' => true,
            'email_is_enabled' => true,
            'push_is_enabled' => false,
            'quiet_hours_are_enabled' => false,
            'quiet_hours_start' => null,
            'quiet_hours_end' => null,
            'delivery_mode' => NotificationDeliveryModeEnum::Immediate,
            'digest_time' => null,
            'digest_weekdays' => null,
            'timezone' => 'Europe/Paris',
            'type_overrides' => null,
        ];
    }

    public function quietBetween(string $start = '22:00', string $end = '08:00'): static
    {
        return $this->state(fn (array $attributes): array => [
            'quiet_hours_are_enabled' => true,
            'quiet_hours_start' => $start,
            'quiet_hours_end' => $end,
        ]);
    }

    public function digestAt(string $time = '18:00'): static
    {
        return $this->state(fn (array $attributes): array => [
            'delivery_mode' => NotificationDeliveryModeEnum::Digest,
            'digest_time' => $time,
        ]);
    }

    public function silent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'in_app_is_enabled' => false,
            'email_is_enabled' => false,
            'push_is_enabled' => false,
        ]);
    }
}
