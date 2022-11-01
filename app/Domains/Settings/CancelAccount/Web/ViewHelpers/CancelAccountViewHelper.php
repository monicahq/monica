<?php

namespace App\Domains\Settings\CancelAccount\Web\ViewHelpers;

class CancelAccountViewHelper
{
    public static function data(): array
    {
        return [
            'url' => [
                'settings' => route('settings.index'),
                'back' => route('settings.index'),
                'destroy' => route('settings.cancel.destroy'),
            ],
        ];
    }
}
