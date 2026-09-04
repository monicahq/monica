<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\CreateEmailSent;
use App\Contracts\HasEmailType;
use App\Models\User;
use Illuminate\Mail\SentMessage;
use Illuminate\Notifications\Events\NotificationSent;
use Symfony\Component\Mime\Email;

/**
 * Write an email that has gone out into the account's journal.
 *
 * Sending and recording used to be the two halves of one job. A notification
 * sends itself, so the recording listens for it instead, and a notification says
 * whether it belongs in the journal by carrying an email type.
 *
 * The Resend transport stamps the message it has just sent with the id Resend
 * gave it, and that id is read back off the message here because it is what the
 * delivery webhooks are matched against. Nothing stamps it where Resend is off,
 * and the row is written without one.
 */
class RecordEmailSent
{
    public function handle(NotificationSent $event): void
    {
        if ($event->channel !== 'mail') {
            return;
        }

        if (! $event->notification instanceof HasEmailType) {
            return;
        }

        if (! $event->notifiable instanceof User) {
            return;
        }

        $message = $event->response instanceof SentMessage
            ? $event->response->getSymfonySentMessage()->getOriginalMessage()
            : null;

        if (! $message instanceof Email) {
            return;
        }

        $body = $message->getHtmlBody();

        new CreateEmailSent(
            account: $event->notifiable->account,
            user: $event->notifiable,
            uuid: $message->getHeaders()->get('X-Resend-Email-ID')?->getBodyAsString(),
            emailType: $event->notification->emailType()->value,
            emailAddress: $event->notifiable->email,
            subject: $message->getSubject() ?? '',
            body: is_string($body) ? $body : '',
        )->execute();
    }
}
