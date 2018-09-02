<?php

namespace App\Notifications;

use App\Models\User\User;
use Illuminate\Bus\Queueable;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\App;
use Illuminate\Queue\SerializesModels;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class StayInTouchEmail extends Notification
{
    use Queueable, SerializesModels;

    /**
     * @return Contact
     */
    protected $contact;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array
     */
    public function via()
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  User $user
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($user)
    {
        App::setLocale($user->locale);

        return (new MailMessage)
            ->subject(trans('mail.stay_in_touch_subject_line', ['name' => $this->contact->name]))
            ->greeting(trans('mail.greetings', ['username' => $user->first_name]))
            ->line(trans_choice('mail.stay_in_touch_subject_description', $this->contact->stay_in_touch_frequency, [
                'name' => $this->contact->name,
                'frequency' => $this->contact->stay_in_touch_frequency,
            ]))
            ->action(trans('mail.footer_contact_info2', ['name' => $this->contact->name]), route('people.show', $this->contact));
    }

    /**
     * Use in test to check the parameter notification.
     *
     * @param Contact $contact
     * @return bool
     */
    public function assertSentFor(Contact $contact) : bool
    {
        return $contact->id == $this->contact->id;
    }
}
