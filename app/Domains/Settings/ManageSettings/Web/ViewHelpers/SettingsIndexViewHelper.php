<?php

namespace App\Domains\Settings\ManageSettings\Web\ViewHelpers;

use App\Models\User;

class SettingsIndexViewHelper
{
    public static function data(User $user): array
    {
        return [
            'is_account_administrator' => $user->is_account_administrator,
            'url' => [
                'preferences' => [
                    'index' => route('settings.preferences.index'),
                ],
                'notifications' => [
                    'index' => route('settings.notifications.index'),
                ],
                'users' => [
                    'index' => route('settings.user.index'),
                ],
                'personalize' => [
                    'index' => route('settings.personalize.index'),
                ],
                'storage' => [
                    'index' => route('settings.storage.index'),
                ],
                'cancel' => [
                    'index' => route('settings.cancel.index'),
                ],
            ],
        ];
    }
}
