<?php

namespace App\Mail;

use App\Models\User\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\App;
use Illuminate\Queue\SerializesModels;

class StayInTouchEmail extends Mailable
{
    use Queueable, SerializesModels;

    protected $contact;
    protected $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Contact $contact, User $user)
    {
        $this->contact = $contact;
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        App::setLocale($this->user->locale);

        return $this->text('emails.reminder.stayintouch')
                    ->subject(trans('mail.stay_in_touch_subject_line', ['name' => $this->contact->name]))
                    ->with([
                        'contact' => $this->contact,
                        'user' => $this->user,
                    ]);
    }
}
