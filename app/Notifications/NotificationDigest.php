<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Contracts\HasEmailType;
use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\Messages\MailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Override;

/**
 * One message carrying everything an account has waiting.
 *
 * The lines are handed in already worded, because each of them was written when
 * its deadline came due and in the language the reader had then. Rewriting them
 * at the hour the summary goes out would be a second chance to get the wording
 * wrong for no gain.
 *
 * Each entry reads as its own two lines, the wording of the reminder in bold and
 * then what it was about, so that a summary of several things does not arrive as
 * one undifferentiated block. They are not translated here for the same reason
 * they are not rebuilt here: they are already words rather than parts to
 * assemble.
 *
 * The emphasis is markdown rather than a tag, because the mail renderer escapes
 * inline html. That is the same exposure the view this replaced had: a reminder
 * somebody named with an asterisk in it renders its own emphasis, and always
 * did.
 */
class NotificationDigest extends Notification implements HasEmailType, ShouldQueue
{
    use Queueable;

    /**
     * @param  list<array{title: string, body: string}>  $lines
     */
    public function __construct(
        public readonly string $heading,
        public readonly array $lines,
    ) {
        $this->onQueue('high');
    }

    /**
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $message = new MailMessage()
            ->subject($this->heading)
            ->greeting($this->heading);

        foreach ($this->lines as $line) {
            $message->line("**{$line['title']}**");
            $message->line($line['body']);
        }

        return $message->line(__('You asked for one message a day rather than one at a time. You can change that wherever your notifications are set up.'));
    }

    #[Override]
    public function emailType(): EmailType
    {
        return EmailType::NotificationDigest;
    }
}
