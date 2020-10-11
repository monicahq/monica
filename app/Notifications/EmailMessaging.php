<?php

namespace App\Notifications;

use App\Models\User\User;
use Illuminate\Support\Facades\App;
use Illuminate\Notifications\Messages\MailMessage;

class EmailMessaging
{
    /**
     * Get the mail representation to verify an email.
     *
     * @param  User $user
     * @return MailMessage
     */
    public static function verifyEmailMail(User $user, $verificationUrl): MailMessage
    {
        App::setLocale($user->locale);

        return (new MailMessage)
            ->subject(trans('mail.confirmation_email_title'))
            ->line(trans('mail.confirmation_email_title'))
            ->line(trans('mail.confirmation_email_intro'))
            ->action(trans('mail.confirmation_email_button'), $verificationUrl)
            ->line(trans('mail.confirmation_email_bottom'));
    }

    /**
     * Get the mail representation to reset a password.
     *
     * @param  User $user
     * @return MailMessage
     */
    public static function resetPasswordMail(User $user, $token): MailMessage
    {
        App::setLocale($user->locale);

        return (new MailMessage)
            ->subject(trans('mail.password_reset_title'))
            ->line(trans('mail.password_reset_title'))
            ->line(trans('mail.password_reset_intro'))
            ->action(trans('mail.password_reset_button'), url(config('app.url').route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()], false)))
            ->line(trans('mail.password_reset_expiration', ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')]))
            ->line(trans('mail.password_reset_bottom'));
    }
}
