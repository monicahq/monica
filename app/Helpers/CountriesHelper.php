<?php

namespace App\Helpers;

use PragmaRX\CountriesLaravel\Package\Facade as Countries;

class CountriesHelper
{
    /**
     * Get list of countries.
     *
     * @return string
     */
    public static function getAll()
    {
        $countries = Countries::all()->map(function ($item, $key) {
            return [
                'id' => $item->cca2,
                'country' => static::getCommonNameLocale($item),
            ];
        });

        return CollectionHelper::sortByCollator($countries, 'country');
    }

    public static function get($iso)
    {
        $country = Countries::where('cca2', mb_strtoupper($iso))->first();
        if ($country->count() === 0) {
            return '';
        }

        return static::getCommonNameLocale($country);
    }

    private static function getCommonNameLocale($country)
    {
        $locale = \App::getLocale();
        $lang = LocaleHelper::getLocaleAlpha($locale);

        return array_get($country, 'translations.'.$lang.'.common',
            array_get($country, 'name.common', '')
        );
    }
}
