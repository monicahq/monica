<?php

namespace App\Http\Controllers\Settings\Preferences\ViewHelpers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Contact;
use App\Helpers\NameHelper;

class PreferencesIndexViewHelper
{
    public static function data(User $user): array
    {
        return [
            'name_order' => self::dtoNameOrder($user),
            'date_format' => self::dtoDateFormat($user),
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
            'nickname' => '007',
            'middle_name' => 'W.',
            'maiden_name' => 'Muller',
        ]);

        $nameExample = NameHelper::formatContactName($user, $contact);

        return [
            'name_example' => $nameExample,
            'name_order' => $user->name_order,
            'url' => [
                'store' => route('settings.preferences.name.store'),
            ],
        ];
    }

    public static function dtoDateFormat(User $user): array
    {
        $date = Carbon::now();
        $collection = collect();

        $collection->push([
            'id' => 1,
            'format' => 'MMM DD, YYYY',
            'value' => $date->isoFormat('MMM DD, YYYY'),
        ]);
        $collection->push([
            'id' => 2,
            'format' => 'DD MMM YYYY',
            'value' => $date->isoFormat('DD MMM YYYY'),
        ]);
        $collection->push([
            'id' => 3,
            'format' => 'YYYY/MM/DD',
            'value' => $date->isoFormat('YYYY/MM/DD'),
        ]);
        $collection->push([
            'id' => 4,
            'format' => 'DD/MM/YYYY',
            'value' => $date->isoFormat('DD/MM/YYYY'),
        ]);

        return [
            'dates' => $collection,
            'date_format' => $user->date_format,
            'human_date_format' => Carbon::now()->isoFormat($user->date_format),
            'url' => [
                'store' => route('settings.preferences.date.store'),
            ],
        ];
    }
}
