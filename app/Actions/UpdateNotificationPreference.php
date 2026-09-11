<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\NotificationDeliveryModeEnum;
use App\Exceptions\NotificationPreferenceIsNotValid;
use App\Helpers\TimezoneCatalogue;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Change how an account wants to be told things: quiet hours, whether things
 * arrive at once or folded into a summary, and the zone both are read in.
 * Which channels carry them is `UpdateNotificationChannel`.
 */
class UpdateNotificationPreference
{
    /** @param  list<int>|null  $digestWeekdays */
    public function __construct(
        private readonly User $user,
        private readonly NotificationPreference $preference,
        private readonly bool $quietHoursAreEnabled,
        private readonly ?string $quietHoursStart = null,
        private readonly ?string $quietHoursEnd = null,
        private readonly NotificationDeliveryModeEnum $deliveryMode = NotificationDeliveryModeEnum::Immediate,
        private readonly ?string $digestTime = null,
        private readonly ?array $digestWeekdays = null,
        private readonly ?string $timezone = null,
    ) {}

    public function execute(): NotificationPreference
    {
        $this->validate();
        $this->update();

        return $this->preference;
    }

    private function validate(): void
    {
        if ($this->preference->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('These preferences are in another account.');
        }

        if ($this->quietHoursAreEnabled && ($this->quietHoursStart === null || $this->quietHoursEnd === null)) {
            throw new NotificationPreferenceIsNotValid('Quiet hours need a time to start and a time to end.');
        }

        foreach ([$this->quietHoursStart, $this->quietHoursEnd, $this->digestTime] as $time) {
            if ($time !== null && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time) !== 1) {
                throw new NotificationPreferenceIsNotValid('This is not a time of day.');
            }
        }

        if ($this->deliveryMode === NotificationDeliveryModeEnum::Digest && $this->digestTime === null) {
            throw new NotificationPreferenceIsNotValid('A daily summary needs an hour to be sent at.');
        }

        foreach ($this->digestWeekdays ?? [] as $weekday) {
            if ($weekday < 0 || $weekday > 6) {
                throw new NotificationPreferenceIsNotValid('That is not a day of the week.');
            }
        }

        if ($this->timezone !== null && TimezoneCatalogue::find($this->timezone) === null) {
            throw new NotificationPreferenceIsNotValid('This is not a time zone the application knows.');
        }
    }

    private function update(): void
    {
        $this->preference->quiet_hours_are_enabled = $this->quietHoursAreEnabled;
        $this->preference->quiet_hours_start = $this->quietHoursStart;
        $this->preference->quiet_hours_end = $this->quietHoursEnd;
        $this->preference->delivery_mode = $this->deliveryMode;
        $this->preference->digest_time = $this->digestTime;
        $this->preference->digest_weekdays = $this->digestWeekdays;
        $this->preference->timezone = $this->timezone ?? $this->preference->timezone;
        $this->preference->save();
    }
}
