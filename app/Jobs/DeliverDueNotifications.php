<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\DeliverNotification;
use App\Actions\NotifyReminderOccurrence;
use App\Enums\NotificationDeliveryModeEnum;
use App\Models\Notification;
use App\Models\ReminderOccurrence;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The sweep that makes a deadline reach somebody, every five minutes. Deadlines that have arrived
 * become notifications first, so that quiet hours are read against the instant a deadline was
 * really due, and only then is everything quiet hours allow out handed to its channel.
 */
class DeliverDueNotifications implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /** How long the lock may outlive a worker that died holding it. Short, since no reminder goes out while it is held. */
    public int $uniqueFor = 900;

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
            ->dueForNotification()
            ->with('reminder.remindable', 'account.user')
            ->chunkById(200, function ($occurrences) use (&$made): void {
                foreach ($occurrences as $occurrence) {
                    try {
                        $made += new NotifyReminderOccurrence(occurrence: $occurrence)->execute();
                    } catch (Throwable $e) {
                        report($e);
                    }
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
                    try {
                        $sent += new DeliverNotification(notification: $notification)->execute() ? 1 : 0;
                    } catch (Throwable $e) {
                        report($e);
                    }
                }
            });

        return $sent;
    }
}
