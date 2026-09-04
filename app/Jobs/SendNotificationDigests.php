<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\SendNotificationDigest;
use App\Enums\NotificationDeliveryModeEnum;
use App\Enums\NotificationTypeEnum;
use App\Models\Notification;
use App\Models\NotificationPreference;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Send each account that asked for one its daily summary, at the hour it chose.
 *
 * It runs every hour and decides per account, because the hour somebody chose is
 * local to them and there is no single moment in the day when every account's
 * summary is due.
 *
 * The chosen hour is never held by quiet hours. It is an explicit setting, and
 * an implicit one silently overruling it would mean a summary that somebody
 * scheduled for six in the evening arriving whenever the product felt able.
 *
 * A day the account excluded is skipped, which is what sparing the weekend
 * means, and a second summary on a day that already had one is not sent.
 */
class SendNotificationDigests implements ShouldQueue
{
    use Queueable;

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

    /**
     * Whether this account's summary is due: the hour it chose has come round
     * today, the day is one it wanted, and it has not had one already.
     */
    private function isDue(NotificationPreference $preference): bool
    {
        $local = CarbonImmutable::now($preference->timezone);
        $weekdays = $preference->digest_weekdays;

        if (is_array($weekdays) && $weekdays !== [] && ! in_array($local->dayOfWeek, $weekdays, false)) {
            return false;
        }

        [$hour, $minute] = array_map(intval(...), explode(':', mb_substr((string) $preference->digest_time, 0, 5)));

        if ($local->lessThan($local->setTime($hour, $minute))) {
            return false;
        }

        return ! Notification::query()
            ->where('account_id', $preference->account_id)
            ->where('type', NotificationTypeEnum::Digest->value)
            ->where('created_at', '>=', $local->setTime($hour, $minute)->utc())
            ->exists();
    }
}
