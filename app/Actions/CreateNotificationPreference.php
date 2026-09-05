<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\NotificationDeliveryModeEnum;
use App\Models\Account;
use App\Models\NotificationPreference;

/**
 * Give an account the row that says how it wants to be told things.
 */
class CreateNotificationPreference
{
    public function __construct(
        private readonly Account $account,
    ) {}

    public function execute(): NotificationPreference
    {
        return NotificationPreference::query()->create([
            'account_id' => $this->account->id,
            'in_app_is_enabled' => true,
            'email_is_enabled' => true,
            'push_is_enabled' => false,
            'quiet_hours_are_enabled' => false,
            'quiet_hours_start' => null,
            'quiet_hours_end' => null,
            'delivery_mode' => NotificationDeliveryModeEnum::Immediate,
            'digest_time' => null,
            'digest_weekdays' => null,
            'timezone' => $this->zone(),
            'type_overrides' => null,
        ]);
    }

    private function zone(): string
    {
        $zone = $this->account->user?->timezone;

        return is_string($zone) && $zone !== '' ? $zone : 'UTC';
    }
}
