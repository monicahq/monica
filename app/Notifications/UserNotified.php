<?php

namespace App\Notifications;

use App\Models\User\User;
use App\Helpers\DateHelper;
use Illuminate\Bus\Queueable;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\App;
use App\Interfaces\MailNotification;
use App\Models\Contact\ReminderOutbox;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as LaravelNotification;

class UserNotified extends LaravelNotification implements ShouldQueue, MailNotification
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var ReminderOutbox
     */
    public $reminderOutbox;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(ReminderOutbox $reminderOutbox)
    {
        $this->reminderOutbox = $reminderOutbox;
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
    public function toMail(User $user) : MailMessage
    {
        App::setLocale($user->locale);

        $contact = Contact::where('account_id', $user->account_id)
            ->findOrFail($this->reminderOutbox->reminder->contact_id);

        $message = (new MailMessage)
            ->subject(trans('mail.subject_line', ['contact' => $contact->name]))
            ->greeting(trans('mail.greetings', ['username' => $user->first_name]))
            ->line(trans_choice('mail.notification_description', $this->reminderOutbox->notification_number_days_before, [
                'count' => $this->reminderOutbox->notification_number_days_before,
                'date' => DateHelper::getShortDate($this->reminderOutbox->reminder->calculateNextExpectedDate()),
            ]))
            ->line($this->reminderOutbox->reminder->title)
            ->line(trans('mail.for', ['name' => $contact->name]))
            ->action(trans('mail.footer_contact_info2', ['name' => $contact->name]), $contact->getLink());

        if (! is_null($this->reminderOutbox->reminder->description)) {
            $message = $message
                ->line(trans('mail.comment', ['comment' => $this->reminderOutbox->reminder->description]));
        }

        return $message;
    }
}
