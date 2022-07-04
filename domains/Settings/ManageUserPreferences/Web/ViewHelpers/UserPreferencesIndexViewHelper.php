<?php

namespace App\Settings\ManageUserPreferences\Web\ViewHelpers;

use App\Helpers\NameHelper;
use App\Models\Contact;
use App\Models\User;
use Carbon\Carbon;

class UserPreferencesIndexViewHelper
{
    public static function data(User $user): array
    {
        return [
            'name_order' => self::dtoNameOrder($user),
            'date_format' => self::dtoDateFormat($user),
            'timezone' => self::dtoTimezone($user),
            'number_format' => self::dtoNumberFormat($user),
            'maps' => self::dtoMapsPreferences($user),
            'locale' => self::dtoLocale($user),
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

    public static function dtoTimezone(User $user): array
    {
        return [
            'timezone' => $user->timezone,
            'url' => [
                'store' => route('settings.preferences.timezone.store'),
            ],
        ];
    }

    public static function dtoNumberFormat(User $user): array
    {
        $collection = collect();
        $collection->push([
            'id' => 1,
            'format' => '1,234.56',
            'value' => User::NUMBER_FORMAT_TYPE_COMMA_THOUSANDS_DOT_DECIMAL,
        ]);
        $collection->push([
            'id' => 2,
            'format' => '1 234,56',
            'value' => User::NUMBER_FORMAT_TYPE_SPACE_THOUSANDS_COMMA_DECIMAL,
        ]);
        $collection->push([
            'id' => 3,
            'format' => '1234.56',
            'value' => User::NUMBER_FORMAT_TYPE_NO_SPACE_DOT_DECIMAL,
        ]);

        return [
            'numbers' => $collection,
            'number_format' => $user->number_format,
            'url' => [
                'store' => route('settings.preferences.number.store'),
            ],
        ];
    }

    public static function dtoMapsPreferences(User $user): array
    {
        $collection = collect();
        $collection->push([
            'id' => 1,
            'type' => trans('account.maps_site_google_maps'),
            'description' => trans('account.maps_site_google_maps_description'),
            'value' => User::MAPS_SITE_GOOGLE_MAPS,
        ]);
        $collection->push([
            'id' => 2,
            'type' => trans('account.maps_site_open_street_maps'),
            'description' => trans('account.maps_site_open_street_maps_description'),
            'value' => User::MAPS_SITE_OPEN_STREET_MAPS,
        ]);

        return [
            'types' => $collection,
            'default_map_site' => $user->default_map_site,
            'default_map_site_i18n' => trans('account.maps_site_'.$user->default_map_site),
            'url' => [
                'store' => route('settings.preferences.maps.store'),
            ],
        ];
    }

    public static function dtoLocale(User $user): array
    {
        return [
            'locale' => $user->locale,
            'locale_i18n' => trans('settings.user_preferences_locale_'.$user->locale),
            'url' => [
                'store' => route('settings.preferences.locale.store'),
            ],
        ];
    }
}
