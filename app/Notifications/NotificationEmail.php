<?php

namespace App\Notifications;

use App\Models\User\User;
use Illuminate\Bus\Queueable;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\App;
use App\Models\Contact\Notification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as LaravelNotification;

class NotificationEmail extends LaravelNotification
{
    use Queueable, SerializesModels;

    /**
     * @return Notification
     */
    protected $notification;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
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

        $contact = Contact::where('account_id', $user->account_id)
            ->findOrFail($this->notification->reminder->contact_id);

        $message = (new MailMessage)
            ->subject(trans('mail.subject_line', ['contact' => $contact->name]))
            ->greeting(trans('mail.greetings', ['username' => $user->first_name]))
            ->line(trans_choice('mail.notification_description', $this->notification->scheduled_number_days_before, [
                'count' => $this->notification->scheduled_number_days_before,
                'date' => $this->notification->reminder->next_expected_date->toDateString(),
            ]))
            ->line($this->notification->reminder->title)
            ->line(trans('mail.for', ['name' => $contact->name]));
        if (! is_null($this->notification->reminder->description)) {
            $message = $message
                ->line(trans('mail.comment', ['comment' => $this->notification->reminder->description]));
        }

        return $message
            ->action(trans('mail.footer_contact_info2', ['name' => $contact->name]), route('people.show', $contact));
    }

    /**
     * Use in test to check the parameter notification.
     *
     * @param Notification $notification
     * @return bool
     */
    public function assertSentFor(Notification $notification) : bool
    {
        return $notification->id == $this->notification->id;
    }
}
