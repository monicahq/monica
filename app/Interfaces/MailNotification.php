<?php

namespace App\Interfaces;

use App\Models\User\User;
use Illuminate\Notifications\Messages\MailMessage;

interface MailNotification
{
    /**
     * Get the nature of the notification.
     * 
     * @return string
     */
    public function getNature() : string;

    /**
     * Get the mail representation of the notification.
     *
     * @param  User $user
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail(User $user) : MailMessage;
}
