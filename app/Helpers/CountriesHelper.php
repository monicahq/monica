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
     *
     * @param string $iso code of the country
     * @return string common name (localized) of the country
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
     * @param string $name  Common name of a country
     * @return string  cca2 code of the country
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

    /**
     * Get the common name of country, in locale version.
     *
     * @param string $country
     * @return string
     */
    private static function getCommonNameLocale($country)
    {
        $locale = App::getLocale();
        $lang = LocaleHelper::getLocaleAlpha($locale);

        return array_get($country, 'translations.'.$lang.'.common',
            array_get($country, 'name.common', '')
        );
    }

    /**
     * Get country for a specific iso code.
     *
     * @param string $iso
     * @return object  the Country element
     */
    public static function getCountry($iso)
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
     * @param string $locale  language code (iso)
     * @return object  the Country element
     */
    public static function getCountryFromLocale($locale)
    {
        $countryCode = self::getDefaultCountryFromLocale($locale);

        if (is_null($countryCode)) {
            $lang = LocaleHelper::getLocaleAlpha($locale);
            $country = Countries::whereLanguage($lang);
            if ($country->count() === 0) {
                return;
            }
        } else {
            $country = Countries::where('cca3', $countryCode);
        }

        return $country->first();
    }

    /**
     * Get default country for a language.
     *
     * @param string $locale   language code (iso)
     * @return string  cca3 code
     */
    private static function getDefaultCountryFromLocale($locale)
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
                $country = null;
                break;
        }

        return $country;
    }

    /**
     * Get default timezone for the country.
     *
     * @param mixed $country  Country element
     * @return string  timezone fo this sountry
     */
    public static function getDefaultTimezone($country)
    {
        // https://en.wikipedia.org/wiki/List_of_tz_database_time_zones
        // https://en.wikipedia.org/wiki/List_of_time_zones_by_country
        switch ($country->cca3) {
            case 'AUS':
                $timezone = 'Australia/Melbourne';
                break;
            case 'CHN':
                $timezone = 'Asia/Shanghai';
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
            case 'CAN':
                $timezone = 'America/Toronto';
                break;
            case 'USA':
                $timezone = 'America/Chicago';
                break;
            default:
                $timezone = $country->hydrate('timezones')->timezones->first()->zone_name;
                break;
        }

        return $timezone;
    }
}
