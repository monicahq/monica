<?php

namespace App\Listeners\Reminder;

use App\Contact;
use App\Events\Reminder\ReminderCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class IncrementNumberOfReminders
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
     * @param  ReminderCreated  $event
     * @return void
     */
    public function handle(ReminderCreated $event)
    {
        $contact = Contact::find($event->reminder->contact_id);
        $contact->number_of_reminders = $contact->number_of_reminders + 1;
        $contact->save();
    }
}
