<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use App\Models\Account\Account;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as LaravelNotification;

class ConfirmEmail extends LaravelNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * @var bool
     */
    public $force;

    public function __construct($force = false)
    {
        $this->force = $force;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array
     */
    public function via()
    {
        if (! $this->force) {
            $first = Account::count() == 1;
            if (! config('monica.signup_double_optin') || $first) {
                return [];
            }
        }

        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($user)
    {
        App::setLocale($user->locale);

        return (new MailMessage)
            ->subject(trans('mail.confirmation_email_title'))
            ->line(trans('mail.confirmation_email_title'))
            ->line(trans('mail.confirmation_email_intro'))
            ->action(
                trans('mail.confirmation_email_button'),
                $this->verificationUrl($user)
            );
    }

    /**
     * Get the verification URL for the given notifiable.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    protected function verificationUrl($notifiable)
    {
        return URL::temporarySignedRoute(
            'verification.verify', Carbon::now()->addMinutes(60), ['id' => $notifiable->getKey()]
        );
    }
}
