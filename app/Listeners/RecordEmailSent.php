<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\CreateEmailSent;
use App\Contracts\HasEmailType;
use App\Models\User;
use Illuminate\Mail\SentMessage;
use Illuminate\Notifications\Events\NotificationSent;
use Symfony\Component\Mime\Email;

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
