<?php

namespace App\Mail;

use App\Helpers\NameHelper;
use App\Models\ContactReminder;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendReminder extends Mailable
{
    use Queueable;
    use SerializesModels;

    public ContactReminder $contactReminder;
    public User $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(ContactReminder $contactReminder, User $user)
    {
        $this->contactReminder = $contactReminder;
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $contact = $this->contactReminder->contact;
        $contactName = NameHelper::formatContactName($this->user, $contact);

        $reason = $this->contactReminder->label;

        return $this->subject(trans('email.notification_reminder_email', ['name' => $contactName]))
            ->markdown('emails.notifications.reminder', [
                'name' => $this->user->name,
                'reason' => $reason,
                'contactName' => $contactName,
            ]);
    }
}
