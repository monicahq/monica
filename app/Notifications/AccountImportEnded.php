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

class AccountImportEnded extends Notification implements HasEmailType, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $imported,
        public readonly int $leftOut,
        public readonly bool $rolledBack,
        public readonly string $url,
    ) {
        $this->onQueue('high');
    }

    /** @return array<int, string> */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        if ($this->rolledBack) {
            return new MailMessage()
                ->subject(__('Your import was undone'))
                ->greeting(__('Nothing was kept'))
                ->line(__('The import stopped before the end, and everything it had written was removed. Your account is as it was before the import started.'))
                ->action(__('See what happened'), $this->url);
        }

        $message = new MailMessage()
            ->subject(__('Your import is finished'))
            ->greeting(__('It is in'))
            ->line(trans_choice(':count item came over.|:count items came over.', $this->imported));

        if ($this->leftOut > 0) {
            $message->line(trans_choice(':count item did not come over, and the report says why.|:count items did not come over, and the report says why.', $this->leftOut));
        }

        return $message->action(__('See the report'), $this->url);
    }

    #[Override]
    public function emailType(): EmailType
    {
        return EmailType::AccountImportEnded;
    }
}
