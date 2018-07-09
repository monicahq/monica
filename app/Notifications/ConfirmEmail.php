<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use App\Models\Account\Account;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ConfirmEmail extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @return array
     */
    public function via()
    {
        $first = Account::count() == 1;
        if (! config('monica.signup_double_optin') || $first) {
            return [];
        }

        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject(trans('mail.confirmation_email_title'))
            ->line(trans('mail.confirmation_email_title'))
            ->line(trans('mail.confirmation_email_intro'))
            ->action(trans('mail.confirmation_email_button'),
                url("confirmation/$notifiable->id/$notifiable->confirmation_code"));
    }
}
