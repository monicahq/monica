<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\SendNotificationDigest;
use App\Enums\NotificationDeliveryModeEnum;
use App\Enums\NotificationTypeEnum;
use App\Models\Notification;
use App\Models\NotificationPreference;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Send each account that asked for one its daily summary, at the hour it chose. It runs every hour
 * and decides per account, because that hour is local to them. A time after the last run of a local
 * day is sent by the first run of the next one, and only by that run, so whatever arrives later waits
 * for the next chosen time.
 */
class SendNotificationDigests implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /** How long the lock may outlive a worker that died holding it. */
    public int $uniqueFor = 3600;

    public function handle(): void
    {
        $sent = 0;
        $folded = 0;

        NotificationPreference::query()
            ->where('delivery_mode', NotificationDeliveryModeEnum::Digest->value)
            ->whereNotNull('digest_time')
            ->with('account.user')
            ->chunkById(200, function ($preferences) use (&$sent, &$folded): void {
                foreach ($preferences as $preference) {
                    if (! $this->isDue($preference)) {
                        continue;
                    }

                    $count = new SendNotificationDigest(account: $preference->account)->execute();
                    $folded += $count;
                    $sent += $count > 0 ? 1 : 0;
                }
            });

        Log::info('Sent the daily notification summaries.', ['digests' => $sent, 'notifications' => $folded]);
    }

    private function isDue(NotificationPreference $preference): bool
    {
        [$hour, $minute] = array_map(intval(...), explode(':', mb_substr((string) $preference->digest_time, 0, 5)));

        $local = CarbonImmutable::now($preference->timezone);
        $slot = $local->setTime($hour, $minute);

        if ($slot->greaterThan($local)) {
            $slot = $slot->subDay();
        }

        if (! $slot->isSameDay($local) && $slot->lessThan($local->subHour())) {
            return false;
        }

        $weekdays = $preference->digest_weekdays;

        if (is_array($weekdays) && $weekdays !== [] && ! in_array($slot->dayOfWeek, $weekdays, false)) {
            return false;
        }

        return ! Notification::query()
            ->where('account_id', $preference->account_id)
            ->where('type', NotificationTypeEnum::Digest->value)
            ->where('created_at', '>=', $slot->utc())
            ->exists();
    }
}
