<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NotificationChannelEnum;
use App\Models\Account;
use App\Models\NotificationChannel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<NotificationChannel> */
class NotificationChannelFactory extends Factory
{
    protected $model = NotificationChannel::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'channel' => NotificationChannelEnum::Email,
            'is_enabled' => false,
            'destination' => null,
            'verification_token_hash' => null,
            'proven_at' => null,
            'proof_code_hash' => null,
            'proof_expires_at' => null,
            'proof_attempts' => 0,
            'proof_sent_count' => 0,
            'proof_last_sent_at' => null,
            'consecutive_failures' => 0,
            'disabled_reason' => null,
        ];
    }

    /**
     * Say which channel the row is about. Call it before `enabled`, `unproven`
     * or `disabled`, which read the channel to decide what to fill in.
     */
    public function channel(NotificationChannelEnum $channel): static
    {
        return $this->state(fn (array $attributes): array => [
            'channel' => $channel,
        ]);
    }

    public function enabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_enabled' => true,
            'destination' => $this->destinationFor($attributes),
            'proven_at' => $this->channelOf($attributes)->provesSilently() ? now() : null,
        ]);
    }

    public function unproven(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_enabled' => false,
            'destination' => $this->destinationFor($attributes),
            'verification_token_hash' => null,
            'proven_at' => null,
        ]);
    }

    public function disabled(string $reason = 'It stopped accepting anything we sent.'): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_enabled' => false,
            'consecutive_failures' => (int) config('monica.notification_channel_failure_threshold'),
            'disabled_reason' => $reason,
        ]);
    }

    /** @param  array<string, mixed>  $attributes */
    private function channelOf(array $attributes): NotificationChannelEnum
    {
        $channel = $attributes['channel'] ?? NotificationChannelEnum::Email;

        return $channel instanceof NotificationChannelEnum
            ? $channel
            : NotificationChannelEnum::from((string) $channel);
    }

    /** @param  array<string, mixed>  $attributes */
    private function destinationFor(array $attributes): ?string
    {
        $channel = $this->channelOf($attributes);

        if (! $channel->needsDestination()) {
            return null;
        }

        return match ($channel) {
            NotificationChannelEnum::Telegram => '918273645',
            NotificationChannelEnum::Sms => '+15705550172',
            default => null,
        };
    }
}
