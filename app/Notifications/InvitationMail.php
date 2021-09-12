<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use App\Models\Account\Invitation;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as LaravelNotification;

class InvitationMail extends LaravelNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

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
     * @param  Invitation  $invitation
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail(Invitation $invitation): MailMessage
    {
        $user = $invitation->invitedBy;
        $acceptInvitationUrl = $this->acceptInvitationUrl($invitation);

        return (new MailMessage)
            ->subject(trans('mail.invitation_title', ['name' => $user->name]))
            ->line(trans('mail.invitation_intro', ['name' => $user->name, 'email' => $user->email]))
            ->line(trans('mail.invitation_link'))
            ->action(trans('mail.invitation_button'), $acceptInvitationUrl)
            ->line(trans('mail.invitation_expiration', ['count' => Config::get('auth.invitation.expire', 2)]));
    }

    /**
     * Get the verification URL for the given notifiable.
     *
     * @param  Invitation  $invitation
     * @return string
     */
    protected function acceptInvitationUrl(Invitation $invitation)
    {
        return URL::temporarySignedRoute(
            'invitations.accept',
            now()->addDays(Config::get('auth.invitation.expire', 2)),
            ['key' => $invitation->invitation_key]
        );
    }
}
