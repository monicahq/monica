<?php

namespace App\Helpers;

use App\Models\User\User;

class MailHelper
{
    /**
     * Get the HTML view that is rendered by the default markdown Laravel
     * notification class.
     * Yes, this is weird, but it's the only way to do it.
     *
     * @param Illuminate\Notifications\Notification $notification
     * @param User $user
     * @return string
     */
    public static function emailView($notification, $user)
    {
        $message = $notification->toMail($user);
        $markdown = new \Illuminate\Mail\Markdown(view(), config('mail.markdown'));
        return $markdown->render($message->markdown, $message->toArray($user));
    }
}
