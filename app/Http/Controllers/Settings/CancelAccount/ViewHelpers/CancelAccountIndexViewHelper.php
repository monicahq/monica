<?php

namespace App\Http\Controllers\Settings\CancelAccount\ViewHelpers;

class CancelAccountIndexViewHelper
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
