<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\SendNotificationDigest;
use App\Enums\NotificationDeliveryModeEnum;
use App\Models\NotificationPreference;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Send each account that asked for one its daily summary, at the hour it chose. It runs every hour
 * and decides per account, because that hour is local to them. A time after the last run of a local
 * day is sent by the first run of the next one, and only by that run, so whatever arrives later waits
 * for the next chosen time. Whether a summary already went out since that time is asked channel by
 * channel, so a channel that refused it is tried again on the next run rather than the next day.
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
                    $slot = $this->slot($preference);

                    if ($slot === null) {
                        continue;
                    }

                    try {
                        $count = new SendNotificationDigest(account: $preference->account, since: $slot)->execute();
                    } catch (Throwable $e) {
                        report($e);

                        continue;
                    }

                    $folded += $count;
                    $sent += $count > 0 ? 1 : 0;
                }
            });

        Log::info('Sent the daily notification summaries.', ['digests' => $sent, 'notifications' => $folded]);
    }

    private function slot(NotificationPreference $preference): ?CarbonImmutable
    {
        [$hour, $minute] = array_map(intval(...), explode(':', mb_substr((string) $preference->digest_time, 0, 5)));

        $local = CarbonImmutable::now($preference->timezone);
        $slot = $local->setTime($hour, $minute);

        if ($slot->greaterThan($local)) {
            $slot = $slot->subDay();
        }

        if (! $slot->isSameDay($local) && $slot->lessThan($local->subHour())) {
            return null;
        }

        $weekdays = $preference->digest_weekdays;

        if (is_array($weekdays) && $weekdays !== [] && ! in_array($slot->dayOfWeek, $weekdays, false)) {
            return null;
        }

        return $slot->utc();
    }
}
