<?php

namespace App\Notifications;

use App\Models\User\User;
use Illuminate\Bus\Queueable;
use App\Models\Contact\Contact;
use App\Models\Contact\Reminder;
use Illuminate\Support\Facades\App;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as LaravelNotification;

class UserRemindedMail extends LaravelNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Reminder
     */
    protected $reminder;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Reminder $reminder)
    {
        $this->reminder = $reminder;
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
     * @return MailMessage
     */
    public function toMail(User $user) : MailMessage
    {
        App::setLocale($user->locale);

        $contact = Contact::where('account_id', $user->account_id)
            ->findOrFail($this->reminder->contact_id);

        $message = (new MailMessage)
            ->subject(trans('mail.subject_line', ['contact' => $contact->name]))
            ->greeting(trans('mail.greetings', ['username' => $user->first_name]))
            ->line(trans('mail.want_reminded_of', ['reason' => $this->reminder->title]))
            ->line(trans('mail.for', ['name' => $contact->name]));
        if (! is_null($this->reminder->description)) {
            $message = $message
                ->line(trans('mail.comment', ['comment' => $this->reminder->description]));
        }

        return $message
            ->action(trans('mail.footer_contact_info2', ['name' => $contact->name]), route('people.show', $contact));
    }

    /**
     * Use in test to check the parameter notification.
     *
     * @param Reminder $reminder
     * @return bool
     */
    public function assertSentFor(Reminder $reminder) : bool
    {
        return $reminder->id == $this->reminder->id;
    }
}
