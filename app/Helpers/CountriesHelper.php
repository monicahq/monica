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
            return ['id' => $item->cca2, 'country' => static::getCommonNameLocale($item)];
        });
        $countries = CollectionHelper::sortByCollator($countries, 'country');

        return $countries->all();
    }

    private static function getCommonNameLocale($country)
    {
        $locale = \App::getLocale();
        $lang = LocaleHelper::getLocaleAlpha($locale);

        return array_get($country, 'translations.'.$lang.'.common', $country->name->common);
    }
}
