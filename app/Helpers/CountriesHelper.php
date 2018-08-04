<?php

namespace App\Helpers;

use Illuminate\Support\Facades\App;
use PragmaRX\CountriesLaravel\Package\Facade as Countries;

class CountriesHelper
{
    /**
     * Get list of countries.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getAll()
    {
        $countries = Countries::all()->map(function ($item) {
            return [
                'id' => $item->cca2,
                'country' => static::getCommonNameLocale($item),
            ];
        });

        return CollectionHelper::sortByCollator($countries, 'country');
    }

    /**
     * Get country name.
     */
    public static function get($iso)
    {
        $country = self::getCountry($iso);
        if (is_null($country)) {
            return '';
        }

        return static::getCommonNameLocale($country);
    }

    /**
     * Find a country by the (english) name of the country.
     * 
     * @return string cca2 code of the country
     */
    public static function find($name)
    {
        $country = Countries::where('name.common', $name)->first();
        if ($country->count() === 0) {
            $country = Countries::where('cca2', mb_strtoupper($name))->first();
        }
        if ($country->count() === 0) {
            return '';
        }

        return $country->cca2;
    }

    private static function getCommonNameLocale($country)
    {
        $locale = App::getLocale();
        $lang = LocaleHelper::getLocaleAlpha($locale);

        return array_get($country, 'translations.'.$lang.'.common',
            array_get($country, 'name.common', '')
        );
    }

    private static function getCountry($iso)
    {
        $country = Countries::where('cca2', mb_strtoupper($iso))->first();
        if ($country->count() === 0) {
            $country = Countries::where('alt_spellings', mb_strtoupper($iso))->first();
        }
        if ($country->count() === 0) {
            return;
        }
        return $country;
    }

    /**
     * Get country for a specific language
     * 
     * @return object
     */
    public static function getCountryFromLang($locale)
    {
        $country = self::getDefaultCountryFromLang($locale);

        if (is_null($country))
        {
            $lang = LocaleHelper::getLocaleAlpha($locale);
            $country = Countries::whereISO639_3($lang);
            if ($country->count() === 0) {
                return;
            }
            $country = $country->first();
        }
        else
        {
            $country = Countries::where('cca3', $country)->first();
        }

        return $country;
    }

    public static function getDefaultCountryFromLang($locale)
    {
        switch (mb_strtolower($locale))
        {
            case 'cs':
                return 'CZE';
            case 'de':
                return 'DEU';
            case 'en':
                return 'USA';
            case 'es':
                return 'ESP';
            case 'fr':
                return 'FRA';
            case 'he':
                return 'ISR';
            case 'it':
                return 'ITA';
            case 'nl':
                return 'NLD';
            case 'pt':
                return 'PRT';
            case 'ru':
                return 'RUS';
            case 'zh':
                return 'CHN';
        }
    }

    public static function getDefaultTimezone($country)
    {
        switch ($country->cca3)
        {
            case 'CHN':
                return 'Asia/Hong_Kong';
            case 'ESP':
                return 'Europe/Madrid';
            case 'PRT':
                return 'Europe/Lisbon';
            case 'RUS':
                return 'Europe/Moscow';
            case 'USA':
                return 'US/Central';
        }
        return $country->hydrate('timezones')->timezones->first()->zone_name;
    }
}