<?php

namespace App\Mail;

use App\Models\User;
use App\Helpers\NameHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\ScheduledContactReminder;

class SendReminder extends Mailable
{
    use Queueable, SerializesModels;

    public ScheduledContactReminder $scheduledContactReminder;
    public User $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(ScheduledContactReminder $contactReminder, User $user)
    {
        $this->scheduledContactReminder = $contactReminder;
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $contact = $this->scheduledContactReminder->reminder->contact;
        $contactName = NameHelper::formatContactName($this->user, $contact);

        $reason = $this->scheduledContactReminder->reminder->label;

        return $this->subject(trans('email.notification_reminder_email', ['name' => $contactName]))
            ->markdown('emails.notifications.reminder', [
                'name' => $this->user->name,
                'reason' => $reason,
                'contactName' => $contactName,
            ]);
    }
}
