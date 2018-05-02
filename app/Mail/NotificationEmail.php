<?php

namespace App\Mail;

use App\User;
use App\Contact;
use App\Reminder;
use App\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\App;
use Illuminate\Queue\SerializesModels;

class NotificationEmail extends Mailable
{
    use Queueable, SerializesModels;

    protected $reminder;
    protected $notification;
    protected $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Notification $notification, User $user)
    {
        $this->reminder = $notification->reminder;
        $this->notification = $notification;
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $contact = Contact::findOrFail($this->reminder->contact_id);

        App::setLocale($this->user->locale);

        return $this->text('emails.reminder.notification')
                    ->subject(trans('mail.notification_subject_line'))
                    ->with([
                        'contact' => $contact,
                        'reminder' => $this->reminder,
                        'notification' => $this->notification,
                        'user' => $this->user,
                    ]);
    }
}
