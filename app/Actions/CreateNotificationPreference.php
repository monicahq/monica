<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationDeliveryModeEnum;
use App\Models\Account;
use App\Models\NotificationChannel;
use App\Models\NotificationPreference;

/**
 * Give an account the row that says how it wants to be told things, and one row
 * per channel it could be told them on.
 */
class CreateNotificationPreference
{
    public function __construct(
        private readonly Account $account,
    ) {}

    public function execute(): NotificationPreference
    {
        $existing = NotificationPreference::query()->where('account_id', $this->account->id)->first();

        if ($existing instanceof NotificationPreference) {
            return $existing;
        }

        $preference = NotificationPreference::query()->create([
            'account_id' => $this->account->id,
            'quiet_hours_are_enabled' => false,
            'quiet_hours_start' => null,
            'quiet_hours_end' => null,
            'delivery_mode' => NotificationDeliveryModeEnum::Immediate,
            'digest_time' => null,
            'digest_weekdays' => null,
            'timezone' => $this->zone(),
            'type_overrides' => null,
        ]);

        $this->channels();

        return $preference;
    }

    private function channels(): void
    {
        foreach (NotificationChannelEnum::cases() as $channel) {
            NotificationChannel::query()->firstOrCreate(
                ['account_id' => $this->account->id, 'channel' => $channel->value],
                ['is_enabled' => ! $channel->needsDestination()],
            );
        }
    }

    private function zone(): string
    {
        $zone = $this->account->user?->timezone;

        return is_string($zone) && $zone !== '' ? $zone : 'UTC';
    }
}
