<?php

namespace App\Mail;

use App\Models\User\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\Models\Contact\Contact;
use App\Models\Contact\Reminder;
use Illuminate\Support\Facades\App;
use Illuminate\Queue\SerializesModels;

class UserRemindedMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $reminder;
    protected $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Reminder $reminder, User $user)
    {
        $this->reminder = $reminder;
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

        return $this->text('emails.reminder.reminder')
                    ->subject(trans('mail.subject_line', ['contact' => $contact->name]))
                    ->with([
                        'contact' => $contact,
                        'reminder' => $this->reminder,
                        'user' => $this->user,
                    ]);
    }
}
