<?php

namespace App\Http\Controllers\Settings\Personalize\ViewHelpers;

class PersonalizeIndexViewHelper
{
    public static function data(): array
    {
        return [
            'url' => [
                'settings' => route('settings.index'),
                'back' => route('settings.index'),
                'manage_relationships' => route('settings.personalize.relationship.index'),
            ],
        ];
    }
}
