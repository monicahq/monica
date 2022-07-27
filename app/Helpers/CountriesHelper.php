<?php

namespace App\Helpers;

use Rinvex\Country\Country;
use Rinvex\Country\CountryLoader;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

class CountriesHelper
{
    /**
     * Get list of countries.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getAll(): Collection
    {
        $x = collect(countries(true, true));
        $countries = $x->map(function (Country $item) {
            return [
                'id' => $item->getIsoAlpha2(),
                'country' => static::getCommonNameLocale($item),
            ];
        });

        return collect($countries->sortByCollator('country'));
    }

    /**
     * Get country name.
     *
     * @param  string  $iso  code of the country
     * @return string common name (localized) of the country
     */
    public static function get($iso): string
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
     * @param  string  $name  Common name of a country
     * @return string iso_3166_1_alpha2 code of the country
     */
    public static function find($name): string
    {
        $country = collect(CountryLoader::where('name.common', $name));
        if ($country->count() === 0) {
            $country = collect(CountryLoader::where('iso_3166_1_alpha2', mb_strtoupper($name)));
        }
        if ($country->count() === 0) {
            return '';
        }

        return (new Country($country->first()))->getIsoAlpha2();
    }

    /**
     * Get the common name of country, in locale version.
     *
     * @param  \Rinvex\Country\Country  $country
     * @return string
     */
    private static function getCommonNameLocale(Country $country): string
    {
        $locale = App::getLocale();
        $lang = LocaleHelper::getLocaleAlpha($locale);

        return $country->getTranslation($lang)['common'];
    }

    /**
     * Get country for a specific iso code.
     *
     * @param  string  $iso
     * @return \Rinvex\Country\Country|null the Country element
     */
    public static function getCountry($iso): ?Country
    {
        $country = collect(CountryLoader::where('iso_3166_1_alpha2', mb_strtoupper($iso)));
        if ($country->count() === 0) {
            $country = collect(CountryLoader::where('alt_spellings', mb_strtoupper($iso)));
        }
        if ($country->count() === 0) {
            return null;
        }

        return new Country($country->first());
    }

    /**
     * Get country for a specific language.
     *
     * @param  string  $locale  language code (iso)
     * @return \Rinvex\Country\Country|null the Country element
     */
    public static function getCountryFromLocale($locale): ?Country
    {
        $countryCode = LocaleHelper::extractCountry($locale);
        if (empty($countryCode)) {
            $countryCode = self::getDefaultCountryFromLocale($locale);
        }

        if (is_null($countryCode)) {
            $lang = LocaleHelper::getLocaleAlpha($locale);
            $country = collect(CountryLoader::where("languages.$lang", '>', '0'));
            if ($country->count() === 0) {
                return null;
            }
        } else {
            $country = collect(CountryLoader::where('iso_3166_1_alpha2', mb_strtoupper($countryCode)));
        }

        return new Country($country->first());
    }

    /**
     * Get default country for a language.
     *
     * @param  string  $locale  language code (iso)
     * @return string|null iso_3166_1_alpha2 code
     */
    private static function getDefaultCountryFromLocale($locale): ?string
    {
        switch (mb_strtolower($locale)) {
            case 'cs':
                $country = 'CZ';
                break;
            case 'en':
                $country = 'US';
                break;
            case 'he':
                $country = 'IL';
                break;
            case 'zh':
                $country = 'CN';
                break;
            case 'de':
            case 'es':
            case 'fr':
            case 'hr':
            case 'it':
            case 'nl':
            case 'pt':
            case 'ru':
            case 'tr':
                $country = mb_strtoupper($locale);
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
     * @param  mixed  $country  Country element
     * @return string timezone fo this sountry
     */
    public static function getDefaultTimezone($country): string
    {
        // https://en.wikipedia.org/wiki/List_of_tz_database_time_zones
        // https://en.wikipedia.org/wiki/List_of_time_zones_by_country
        switch ($country->getIsoAlpha3()) {
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
                $timezone = collect($country->getTimezones())->first();
                break;
        }

        return $timezone;
    }
}
