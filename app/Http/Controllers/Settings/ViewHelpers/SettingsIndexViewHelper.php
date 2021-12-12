<?php

namespace App\Http\Controllers\Settings\ViewHelpers;

use App\Models\User;

class SettingsIndexViewHelper
{
    public static function data(User $user): array
    {
        return [
            'is_account_administrator' => $user->is_account_administrator,
            'url' => [
                'users' => [
                    'index' => route('settings.user.index'),
                ],
                'cancel' => [
                    'index' => route('settings.cancel.index'),
                ],
            ],
        ];
    }
}
