<?php

namespace App\Listeners\Reminder;

use App\Contact;
use App\Events\Reminder\ReminderDeleted;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class DecreaseNumberOfReminders
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  ReminderDeleted  $event
     * @return void
     */
    public function handle(ReminderDeleted $event)
    {
        $contact = Contact::find($event->reminder->contact_id);
        $contact->number_of_reminders = $contact->number_of_reminders - 1;

        if ($contact->number_of_reminders < 1) {
            $contact->number_of_reminders = 0;
        }
        $contact->save();
    }
}
