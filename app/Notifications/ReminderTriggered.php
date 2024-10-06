<?php

namespace App\Notifications;

use App\Models\UserNotificationChannel;
use App\Models\UserNotificationSent;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

class ReminderTriggered extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(
        private UserNotificationChannel $channel,
        private string $content,
        private string $contactName
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        switch ($this->channel->type) {
            case UserNotificationChannel::TYPE_EMAIL:
                return ['mail'];
            case UserNotificationChannel::TYPE_TELEGRAM:
                return ['telegram'];
        }

        return [];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        UserNotificationSent::create([
            'user_notification_channel_id' => $this->channel->id,
            'sent_at' => Carbon::now(),
            'subject_line' => $this->content,
        ]);

        return (new MailMessage)
            ->subject(trans('Reminder for :name', ['name' => $this->contactName]))
            ->line(trans('You wanted to be reminded of the following:'))
            ->line($this->content)
            ->line(trans('for'))
            ->line($this->contactName)
            ->line(trans('Test email for Monica'));
    }

    public function toTelegram($notifiable)
    {
        UserNotificationSent::create([
            'user_notification_channel_id' => $this->channel->id,
            'sent_at' => Carbon::now(),
            'subject_line' => $this->content,
        ]);

        // content contains the label of the ContactReminder object
        $content = trans('🔔 Reminder: :label for :contactName', [
            'label' => $this->content,
            'contactName' => $this->contactName,
        ]);

        return TelegramMessage::create()
            ->content($content)
            ->to($this->channel->content);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
