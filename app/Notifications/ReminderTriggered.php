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
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        if ($this->channel->type === UserNotificationChannel::TYPE_EMAIL) {
            return ['mail'];
        }

        if ($this->channel->type === UserNotificationChannel::TYPE_TELEGRAM) {
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

        return (new MailMessage())
                    ->subject(trans('email.notification_reminder_email', ['name' => $this->contactName]))
                    ->line(trans('email.reminder_triggered_intro'))
                    ->line($this->content)
                    ->line(trans('email.reminder_triggered_for'))
                    ->line($this->contactName)
                    ->line(trans('email.reminder_triggered_signature'));
    }

    public function toTelegram($notifiable)
    {
        UserNotificationSent::create([
            'user_notification_channel_id' => $this->channel->id,
            'sent_at' => Carbon::now(),
            'subject_line' => $this->content,
        ]);

        // content contains the label of the ContactReminder object
        $content = '🔔 Reminder: '.
            $this->content.' '.
            trans('email.reminder_triggered_for').' '.
            $this->contactName;

        return TelegramMessage::create()
            ->to($this->channel->content)
            ->content($content);
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
