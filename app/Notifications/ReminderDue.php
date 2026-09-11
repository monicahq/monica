<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Contracts\CarriesOnAccountChannel;
use App\Contracts\HasEmailType;
use App\Enums\EmailType;
use App\Models\NotificationChannel;
use App\Models\User;
use App\Notifications\Concerns\BuildsMessagesForAnyChannel;
use App\Notifications\Messages\MailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Override;

class ReminderDue extends Notification implements CarriesOnAccountChannel, HasEmailType, ShouldQueue
{
    use BuildsMessagesForAnyChannel;
    use Queueable;

    public function __construct(
        public readonly string $reminderName,
        public readonly string $recordName,
        public readonly string $sourceDate,
        public readonly string $whenItIsAbout,
        public readonly NotificationChannel $channel,
    ) {
        $this->onQueue('high');
    }

    /**
     * The channel this one goes out on, named the way Laravel names it. A
     * channel the product cannot hand off, the in application one above all,
     * returns nothing: its row in the database is the delivery.
     *
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        $driver = $this->channel->channel->laravelChannel();

        return $driver === null ? [] : [$driver];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return new MailMessage()
            ->subject($this->reminderName)
            ->greeting($this->reminderName)
            ->line($this->whenItIsAbout)
            ->line(__('It is about :name, and the date it reads is :date.', [
                'name' => $this->recordName,
                'date' => $this->sourceDate,
            ]))
            ->line(__('You asked to be told this when you added the reminder. You can change it, or stop it, wherever that date is written down.'));
    }

    #[Override]
    protected function plainText(): string
    {
        return implode(' ', [
            $this->reminderName.'.',
            $this->whenItIsAbout,
            __('It is about :name, and the date it reads is :date.', [
                'name' => $this->recordName,
                'date' => $this->sourceDate,
            ]),
        ]);
    }

    #[Override]
    public function notificationChannel(): NotificationChannel
    {
        return $this->channel;
    }

    #[Override]
    public function emailType(): EmailType
    {
        return EmailType::ReminderDue;
    }
}
