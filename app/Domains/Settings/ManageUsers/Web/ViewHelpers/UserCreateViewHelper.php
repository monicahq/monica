<?php

namespace App\Domains\Settings\ManageUsers\Web\ViewHelpers;

class UserCreateViewHelper
{
    public static function data(): array
    {
        return [
            'url' => [
                'settings' => route('settings.index'),
                'back' => route('settings.user.index'),
                'store' => route('settings.user.store'),
            ],
        ];
    }
}
