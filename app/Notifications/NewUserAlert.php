<?php

namespace App\Notifications;

use App\Models\User\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Messages\MailMessage;

class NewUserAlert extends Notification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

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
     * @param  string $mail
     * @return MailMessage
     */
    public function toMail() : MailMessage
    {
        $user = $this->user;

        return (new MailMessage)
            ->subject("New registration: {$user->first_name} {$user->last_name}")
            ->greeting('New registration')
            ->line("User: {$user->first_name} {$user->last_name}")
            ->line("ID: {$user->id}")
            ->line("Email: {$user->email}");
    }
}
