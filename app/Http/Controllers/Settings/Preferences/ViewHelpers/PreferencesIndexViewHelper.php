<?php

namespace App\Http\Controllers\Settings\Preferences\ViewHelpers;

use App\Models\User;
use App\Models\Contact;
use App\Helpers\NameHelper;

class PreferencesIndexViewHelper
{
    public static function data(User $user): array
    {
        return [
            'name_order' => self::dtoNameOrder($user),
            'url' => [
                'settings' => route('settings.index'),
                'back' => route('settings.index'),
            ],
        ];
    }

    public static function dtoNameOrder(User $user): array
    {
        $contact = new Contact([
            'first_name' => 'James',
            'last_name' => 'Bond',
            'surname' => '007',
            'middle_name' => 'W.',
            'maiden_name' => 'Muller',
        ]);

        $nameExample = NameHelper::formatContactName($user, $contact);

        return [
            'name_example' => $nameExample,
            'name_order' => $user->name_order,
            'url' => [
                'store' => route('settings.preferences.store'),
            ],
        ];
    }
}
