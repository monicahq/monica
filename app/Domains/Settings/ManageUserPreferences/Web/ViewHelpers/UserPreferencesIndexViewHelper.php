<?php

namespace App\Domains\Settings\ManageUserPreferences\Web\ViewHelpers;

use App\Helpers\MonetaryNumberHelper;
use App\Helpers\NameHelper;
use App\Models\Contact;
use App\Models\User;
use Carbon\Carbon;

class UserPreferencesIndexViewHelper
{
    public static function data(User $user): array
    {
        return [
            'help' => self::dtoHelp($user),
            'name_order' => self::dtoNameOrder($user),
            'date_format' => self::dtoDateFormat($user),
            'timezone' => self::dtoTimezone($user),
            'number_format' => self::dtoNumberFormat($user),
            'distance_format' => self::dtoDistanceFormat($user),
            'maps' => self::dtoMapsPreferences($user),
            'locale' => self::dtoLocale($user),
            'url' => [
                'settings' => route('settings.index'),
                'back' => route('settings.index'),
            ],
        ];
    }

    public static function dtoHelp(User $user): array
    {
        return [
            'help_shown' => $user->help_shown,
            'url' => [
                'store' => route('settings.preferences.help.store'),
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
        $duser = new User;
        $duser->number_format = User::NUMBER_FORMAT_TYPE_LOCALE_DEFAULT;
        $default = MonetaryNumberHelper::formatValue($duser, 123456);

        $collection = collect();
        $collection->push([
            'id' => 0,
            'format' => trans('Locale default: :value', ['value' => $default]),
            'value' => User::NUMBER_FORMAT_TYPE_LOCALE_DEFAULT,
        ]);
        $collection->push([
            'id' => 1,
            'format' => '1,234.56',
            'value' => User::NUMBER_FORMAT_TYPE_COMMA_THOUSANDS_DOT_DECIMAL,
        ]);
        $collection->push([
            'id' => 2,
            'format' => '1 234,56',
            'value' => User::NUMBER_FORMAT_TYPE_SPACE_THOUSANDS_COMMA_DECIMAL,
        ]);
        $collection->push([
            'id' => 3,
            'format' => '1.234,56',
            'value' => User::NUMBER_FORMAT_TYPE_DOT_THOUSANDS_COMMA_DECIMAL,
        ]);
        $collection->push([
            'id' => 4,
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

    public static function dtoDistanceFormat(User $user): array
    {
        return [
            'number_format' => $user->distance_format,
            'url' => [
                'store' => route('settings.preferences.distance.store'),
            ],
        ];
    }

    public static function dtoMapsPreferences(User $user): array
    {
        $collection = collect();
        $collection->push([
            'id' => 1,
            'type' => trans('Google Maps'),
            'description' => trans('Google Maps offers the best accuracy and details, but it is not ideal from a privacy standpoint.'),
            'value' => User::MAPS_SITE_GOOGLE_MAPS,
        ]);
        $collection->push([
            'id' => 2,
            'type' => trans('Open Street Maps'),
            'description' => trans('Open Street Maps is a great privacy alternative, but offers less details.'),
            'value' => User::MAPS_SITE_OPEN_STREET_MAPS,
        ]);

        $i18n = match ($user->default_map_site) {
            User::MAPS_SITE_GOOGLE_MAPS => trans('Google Maps'),
            User::MAPS_SITE_OPEN_STREET_MAPS => trans('Open Street Maps'),
            default => trans('Google Maps'),
        };

        return [
            'types' => $collection,
            'default_map_site' => $user->default_map_site,
            'default_map_site_i18n' => $i18n,
            'url' => [
                'store' => route('settings.preferences.maps.store'),
            ],
        ];
    }

    public static function dtoLocale(User $user): array
    {
        return [
            'id' => $user->locale,
            'name' => self::language($user->locale),
            'dir' => htmldir(),
            'locales' => collect(config('localizer.supported_locales'))
                ->map(fn (string $locale) => [
                    'id' => $locale,
                    'name' => self::language($locale),
                ])
                ->sortByCollator('name'),
            'url' => [
                'store' => route('settings.preferences.locale.store'),
            ],
        ];
    }

    public static function language(?string $code): string
    {
        return $code !== null ? __('auth.lang', [], $code) : '';
    }
}
