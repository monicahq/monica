<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\NotificationDeliveryModeEnum;
use App\Models\Account;
use App\Models\NotificationPreference;

/**
 * Give an account the row that says how it wants to be told things.
 *
 * It is made with the account rather than the first time somebody opens a
 * settings screen, because delivery reads it on every single send and is written
 * on the promise that it is there. A missing row would mean either an account
 * that silently receives nothing or a default buried in the delivery code where
 * nobody can see it.
 *
 * The defaults are the two channels that exist, no quiet hours, and everything
 * arriving as it happens. Push is off because nothing delivers it yet, and quiet
 * hours are off because guessing at somebody's sleeping hours and then silently
 * holding their reminders is worse than telling them at the wrong time once.
 *
 * The zone is the one the account's user is in. Quiet hours and the digest are
 * the only things read in it, and both are about the hours of somebody's day.
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

    /**
     * The zone of the one user the account holds, and UTC for an account that
     * somehow holds none, since a preference with no zone cannot be read at all.
     */
    private function zone(): string
    {
        $zone = $this->account->user?->timezone;

        return is_string($zone) && $zone !== '' ? $zone : 'UTC';
    }
}
