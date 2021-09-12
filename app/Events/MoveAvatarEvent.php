<?php

namespace App\Events;

use App\Models\Contact\Contact;
use Illuminate\Queue\SerializesModels;

class MoveAvatarEvent extends Event
{
    use SerializesModels;

    /**
     * The contact.
     *
     * @var Contact
     */
    public $contact;

    /**
     * Create a new event instance.
     *
     * @param  Contact  $contact
     * @return void
     */
    public function __construct($contact)
    {
        $this->contact = $contact;
    }
}
