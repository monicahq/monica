<?php

namespace App\Interfaces;

use App\Models\User\User;
use Illuminate\Notifications\Messages\MailMessage;

interface MailNotification
{
    /**
     * Get the mail representation of the notification.
     *
     * @param  User $user
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail(User $user): MailMessage;
}
