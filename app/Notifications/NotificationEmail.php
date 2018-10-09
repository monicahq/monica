<?php

namespace App\Notifications;

use App\Models\User\User;
use App\Helpers\DateHelper;
use Illuminate\Bus\Queueable;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\App;
use App\Models\Contact\Notification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as LaravelNotification;

class NotificationEmail extends LaravelNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Notification
     */
    public $notification;

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
     * @return MailMessage
     */
    public function toMail(User $user) : MailMessage
    {
        App::setLocale($user->locale);

        $contact = Contact::where('account_id', $user->account_id)
            ->findOrFail($this->notification->reminder->contact_id);

        $message = (new MailMessage)
            ->subject(trans('mail.subject_line', ['contact' => $contact->name]))
            ->greeting(trans('mail.greetings', ['username' => $user->first_name]))
            ->line(trans_choice('mail.notification_description', $this->notification->scheduled_number_days_before, [
                'count' => $this->notification->scheduled_number_days_before,
                'date' => DateHelper::getShortDate($this->notification->reminder->next_expected_date),
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
