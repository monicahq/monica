<?php

namespace App\Listeners;

use Carbon\Carbon;
use App\Notifications\UserNotified;
use App\Notifications\UserReminded;
use App\Models\Contact\ReminderSent;
use Illuminate\Notifications\Events\NotificationSent as NotificationSentEvent;

class NotificationSent
{
    /**
     * Handle the NotificationSent event.
     *
     * @param  NotificationSentEvent $event
     * @return void
     */
    public function handle(NotificationSentEvent $event)
    {
        $reminderSent = new ReminderSent;
        $reminderSent->account_id = $event->notification->reminderOutbox->account_id;
        $reminderSent->reminder_id = $event->notification->reminderOutbox->reminder_id;
        $reminderSent->user_id = $event->notification->reminderOutbox->user_id;
        $reminderSent->planned_date = $event->notification->reminderOutbox->planned_date;
        $reminderSent->sent_date = Carbon::now();
        $reminderSent->frequency_type = is_null($event->notification->reminderOutbox->reminder) ? null : $event->notification->reminderOutbox->reminder->frequency_type;
        $reminderSent->frequency_number = is_null($event->notification->reminderOutbox->reminder) ? null : $event->notification->reminderOutbox->reminder->frequency_number;
        $reminderSent->html_content = (new UserNotified($event->notification->reminderOutbox));

        if ($event->notification instanceof UserNotified) {
            $reminderSent->nature = 'notification';
        }

        if ($event->notification instanceof UserReminded) {
            $reminderSent->nature = 'reminder';
        }
        $reminderSent->save();
    }
}
