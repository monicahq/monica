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
     * Get country for a specific language.
     *
     * @return object
     */
    public static function getCountryFromLang($locale)
    {
        $country = self::getDefaultCountryFromLang($locale);

        if (is_null($country)) {
            $lang = LocaleHelper::getLocaleAlpha($locale);
            $country = Countries::whereISO639_3($lang);
            if ($country->count() === 0) {
                return;
            }
            $country = $country->first();
        } else {
            $country = Countries::where('cca3', $country)->first();
        }

        return $country;
    }

    public static function getDefaultCountryFromLang($locale)
    {
        switch (mb_strtolower($locale)) {
            case 'cs':
                $country = 'CZE';
                break;
            case 'de':
                $country = 'DEU';
                break;
            case 'en':
                $country = 'USA';
                break;
            case 'es':
                $country = 'ESP';
                break;
            case 'fr':
                $country = 'FRA';
                break;
            case 'he':
                $country = 'ISR';
                break;
            case 'it':
                $country = 'ITA';
                break;
            case 'nl':
                $country = 'NLD';
                break;
            case 'pt':
                $country = 'PRT';
                break;
            case 'ru':
                $country = 'RUS';
                break;
            case 'zh':
                $country = 'CHN';
                break;
            default:
                $country = '';
                break;
        }

        return $country;
    }

    public static function getDefaultTimezone($country)
    {
        switch ($country->cca3) {
            case 'CHN':
                $timezone = 'Asia/Hong_Kong';
                break;
            case 'ESP':
                $timezone = 'Europe/Madrid';
                break;
            case 'PRT':
                $timezone = 'Europe/Lisbon';
                break;
            case 'RUS':
                $timezone = 'Europe/Moscow';
                break;
            case 'USA':
                $timezone = 'US/Central';
                break;
            default:
                $timezone = $country->hydrate('timezones')->timezones->first()->zone_name;
                break;
        }

        return $timezone;
    }
}
