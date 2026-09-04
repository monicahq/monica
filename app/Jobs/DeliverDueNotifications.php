<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\DeliverNotification;
use App\Actions\NotifyReminderOccurrence;
use App\Enums\NotificationDeliveryModeEnum;
use App\Models\Notification;
use App\Models\ReminderOccurrence;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * The sweep that makes a deadline reach somebody.
 *
 * Two steps, in this order and for a reason. First every deadline whose instant
 * has arrived becomes the notifications that will carry it, so that quiet hours
 * are read against the instant the deadline was really due. Then everything
 * quiet hours now allow out is handed to its channel.
 *
 * Five minutes is the cadence, because a reminder that arrives an hour after it
 * was due is a reminder somebody stops trusting.
 *
 * An account that folds everything into a daily summary is skipped in the second
 * step. Its notifications are made here like everybody else's and wait for
 * {@see SendNotificationDigests} to carry them, which is why they are left
 * alone rather than filtered out of existence.
 *
 * It holds no rules of its own beyond that ordering.
 */
class DeliverDueNotifications implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $made = $this->makeTheNotifications();
        $sent = $this->handOutWhatMayGo();

        Log::info('Swept the reminder deadlines that came due.', ['made' => $made, 'sent' => $sent]);
    }

    private function makeTheNotifications(): int
    {
        $made = 0;

        ReminderOccurrence::query()
            ->due()
            ->with('reminder.remindable', 'account.user')
            ->chunkById(200, function ($occurrences) use (&$made): void {
                foreach ($occurrences as $occurrence) {
                    $made += new NotifyReminderOccurrence(occurrence: $occurrence)->execute();
                }
            });

        return $made;
    }

    private function handOutWhatMayGo(): int
    {
        $sent = 0;

        Notification::query()
            ->deliverable()
            ->whereHas('account.notificationPreference', fn ($query) => $query
                ->where('delivery_mode', NotificationDeliveryModeEnum::Immediate->value))
            ->with('user', 'account', 'notifiable.reminder.remindable')
            ->chunkById(200, function ($notifications) use (&$sent): void {
                foreach ($notifications as $notification) {
                    $sent += new DeliverNotification(notification: $notification)->execute() ? 1 : 0;
                }
            });

        return $sent;
    }
}
