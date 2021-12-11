<?php

namespace App\Http\Controllers\Settings\ViewHelpers;

class SettingsIndexViewHelper
{
    public static function data(): array
    {
        return [
            'url' => [
                'users' => [
                    'index' => route('settings.user.index'),
                ],
            ],
        ];
    }
}
