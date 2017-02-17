<?php

namespace App\Listeners\Contact;

use App\Events\Contact\ContactCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SetAvatarColor
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
     * @param  ContactCreated  $event
     * @return void
     */
    public function handle(ContactCreated $event)
    {
        $event->contact->setAvatarColor();
    }
}
