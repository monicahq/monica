<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationTypeEnum;
use App\Models\Account;
use App\Models\Notification;
use App\Models\ReminderOccurrence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function definition(): array
    {
        $scheduledFor = now();

        return [
            'account_id' => Account::factory(),
            'user_id' => fn (array $attributes): int => User::factory()->create(['account_id' => $attributes['account_id']])->id,
            'type' => NotificationTypeEnum::ReminderDue,
            'channel' => NotificationChannelEnum::InApp,
            'notifiable_type' => ReminderOccurrence::class,
            'notifiable_id' => fn (): int => ReminderOccurrence::factory()->create()->id,
            'title' => 'Birthday, Michael Scott',
            'body' => 'It is in seven days.',
            'payload' => null,
            'locale' => 'en',
            'scheduled_for' => $scheduledFor,
            'held_until' => $scheduledFor,
            'sent_at' => null,
            'delivered_at' => null,
            'failed_at' => null,
            'failure_reason' => null,
            'attempts' => 0,
            'read_at' => null,
            'digest_id' => null,
        ];
    }

    /**
     * Indicate that the notification goes out by email rather than appearing in
     * the application.
     */
    public function email(): static
    {
        return $this->state(fn (array $attributes): array => [
            'channel' => NotificationChannelEnum::Email,
        ]);
    }

    /**
     * Indicate that quiet hours are holding the notification until the time
     * given.
     */
    public function heldUntil(string $when): static
    {
        return $this->state(fn (array $attributes): array => [
            'held_until' => $when,
        ]);
    }

    /**
     * Indicate that the notification has gone out.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'sent_at' => now(),
            'attempts' => 1,
        ]);
    }

    /**
     * Indicate that the notification is a summary rather than one of the things
     * summarised, which is why it is about nothing in particular.
     */
    public function digest(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => NotificationTypeEnum::Digest,
            'channel' => NotificationChannelEnum::Email,
            'notifiable_type' => null,
            'notifiable_id' => null,
        ]);
    }
}
