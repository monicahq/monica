<?php

namespace App\Domains\Settings\ManageNotificationChannels\Web\ViewHelpers;

use App\Helpers\DateHelper;
use App\Models\User;
use App\Models\UserNotificationChannel;

class NotificationsLogIndexViewHelper
{
    public static function data(UserNotificationChannel $channel, User $user): array
    {
        $channels = $channel->userNotificationSent()->orderBy('sent_at', 'desc')->get();

        $notificationsCollection = $channels->map(function ($notification) use ($user) {
            return [
                'id' => $notification->id,
                'sent_at' => DateHelper::format($notification->sent_at, $user),
                'subject_line' => $notification->subject_line,
                'error' => $notification->error,
            ];
        });

        $i18n = match ($channel->type) {
            'email' => trans('Email address'),
            'telegram' => trans('Telegram'),
            default => trans('Email'),
        };

        return [
            'channel' => [
                'id' => $channel->id,
                'type' => $i18n,
                'label' => $channel->label,
            ],
            'notifications' => $notificationsCollection,
            'url' => [
                'settings' => route('settings.index'),
                'channels' => route('settings.notifications.index'),
                'back' => route('settings.index'),
            ],
        ];
    }
}
