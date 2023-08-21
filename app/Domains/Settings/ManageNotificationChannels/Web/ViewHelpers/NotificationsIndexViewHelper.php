<?php

namespace App\Domains\Settings\ManageNotificationChannels\Web\ViewHelpers;

use App\Models\User;
use App\Models\UserNotificationChannel;

class NotificationsIndexViewHelper
{
    public static function data(User $user): array
    {
        $channels = $user->notificationChannels;

        // emails
        $emails = $channels->filter(fn ($channel) => $channel->type === 'email');
        $emailsCollection = $emails->map(fn ($channel) => self::dtoEmail($channel));

        // telegram
        $telegram = $channels->filter(fn ($channel) => $channel->type === 'telegram')->first();

        return [
            'emails' => $emailsCollection,
            'telegram' => [
                'data' => $telegram ? self::dtoTelegram($telegram) : null,
                'telegram_env_variable_set' => config('services.telegram-bot-api.token') !== null,
            ],
            'url' => [
                'settings' => route('settings.index'),
                'back' => route('settings.index'),
                'store' => route('settings.notifications.store'),
                'store_telegram' => route('settings.notifications.telegram.store'),
            ],
        ];
    }

    public static function dtoEmail(UserNotificationChannel $channel): array
    {
        return [
            'id' => $channel->id,
            'type' => $channel->type,
            'label' => $channel->label,
            'content' => $channel->content,
            'active' => $channel->active,
            'verified_at' => $channel->verified_at ? $channel->verified_at->format('Y-m-d H:i:s') : null,
            'preferred_time' => $channel->preferred_time->format('H:i'),
            'url' => [
                'store' => route('settings.notifications.store'),
                'send_test' => route('settings.notifications.test.store', [
                    'notification' => $channel->id,
                ]),
                'toggle' => route('settings.notifications.toggle.update', [
                    'notification' => $channel->id,
                ]),
                'logs' => route('settings.notifications.log.index', [
                    'notification' => $channel->id,
                ]),
                'destroy' => route('settings.notifications.destroy', [
                    'notification' => $channel->id,
                ]),
            ],
        ];
    }

    public static function dtoTelegram(UserNotificationChannel $channel): array
    {
        return [
            'id' => $channel->id,
            'type' => $channel->type,
            'active' => $channel->active,
            'verified_at' => $channel->verified_at ? $channel->verified_at->format('Y-m-d H:i:s') : null,
            'preferred_time' => $channel->preferred_time->format('H:i'),
            'url' => [
                'open' => config('services.telegram-bot-api.bot_url').'?start='.$channel->verification_token,
                'send_test' => route('settings.notifications.test.store', [
                    'notification' => $channel->id,
                ]),
                'toggle' => route('settings.notifications.toggle.update', [
                    'notification' => $channel->id,
                ]),
                'logs' => route('settings.notifications.log.index', [
                    'notification' => $channel->id,
                ]),
                'destroy' => route('settings.notifications.destroy', [
                    'notification' => $channel->id,
                ]),
            ],
        ];
    }
}
