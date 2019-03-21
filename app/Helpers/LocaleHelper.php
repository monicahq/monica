<?php

namespace App\Helpers;

use Matriphe\ISO639\ISO639;
use Illuminate\Support\Facades\App;
use libphonenumber\PhoneNumberUtil;
use Illuminate\Support\Facades\Auth;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\NumberParseException;

class LocaleHelper
{
    private const LANG_SPLIT = '/(-|_)/';

    /**
     * Get the current or default locale.
     *
     * @return string
     */
    public static function getLocale()
    {
        if (Auth::check()) {
            $locale = Auth::user()->locale;
        } else {
            $locale = app('language.detector')->detect() ?: config('app.locale');
        }

        return $locale;
    }

    /**
     * Get the current lang from locale.
     *
     * @return string  lang, lowercase form.
     */
    public static function getLang($locale = null)
    {
        if (is_null($locale)) {
            $locale = App::getLocale();
        }
        if (preg_match(self::LANG_SPLIT, $locale)) {
            $locale = preg_split(self::LANG_SPLIT, $locale, 2)[0];
        }

        return mb_strtolower($locale);
    }

    /**
     * Get the current country from locale.
     *
     * @return string  country, uppercase form.
     */
    public static function getCountry($locale = null)
    {
        $countryCode = self::extractCountry($locale);

        if (is_null($countryCode)) {
            $country = CountriesHelper::getCountryFromLocale($locale);
            $countryCode = $country->cca2;
        }

        return mb_strtoupper($countryCode);
    }

    /**
     * Extract the current country from locale, i.e. 'en-US' will return 'US'.
     * If no country is present in the locale, it will return null.
     *
     * @return string|null  country, uppercase form.
     */
    public static function extractCountry($locale = null)
    {
        if (is_null($locale)) {
            $locale = App::getLocale();
        }
        if (preg_match(self::LANG_SPLIT, $locale)) {
            $locale = preg_split(self::LANG_SPLIT, $locale, 2)[1];

            return mb_strtoupper($locale);
        }
    }

    /**
     * Get the list of avalaible languages.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getLocaleList()
    {
        $locales = collect([]);
        foreach (config('lang-detector.languages') as $lang) {
            $name = trans('settings.locale_'.$lang);
            if ($name == 'settings.locale_'.$lang) {
                // The name of the new language is not already set, even in english
                $name = $lang;
            }
            $locales->push([
                'lang' => $lang,
                'name' => $name,
            ]);
        }

        return $locales->sortByCollator('name');
    }

    /**
     * Get the direction: left to right/right to left.
     *
     * @return string
     */
    public static function getDirection()
    {
        $lang = self::getLang();
        switch ($lang) {
            // Source: https://meta.wikimedia.org/wiki/Template:List_of_language_names_ordered_by_code
            case 'ar':
            case 'arc':
            case 'dv':
            case 'fa':
            case 'ha':
            case 'he':
            case 'khw':
            case 'ks':
            case 'ku':
            case 'ps':
            case 'ur':
            case 'yi':
                return 'rtl';
            default:
                return 'ltr';
        }
    }

    /**
     * Association ISO-639-1 => ISO-639-2.
     */
    private static $locales = [];

    /**
     * Get ISO-639-2/t (three-letter codes) from ISO-639-1 (two-letters code).
     *
     * @param  string $locale
     * @return string
     */
    public static function getLocaleAlpha($locale)
    {
        if (array_has(static::$locales, $locale)) {
            return array_get(static::$locales, $locale);
        }
        $locale = mb_strtolower($locale);
        $languages = (new ISO639)->allLanguages();
        $lang = '';
        foreach ($languages as $l) {
            if ($l[0] == $locale) {
                $lang = $l[1];
                break;
            }
        }
        static::$locales[$locale] = $lang;

        return $lang;
    }

    /**
     * Format phone number by country.
     *
     * @param string $tel
     * @param string|null $iso
     * @param int $format
     * @return string
     */
    public static function formatTelephoneNumberByISO(string $tel, $iso, int $format = PhoneNumberFormat::INTERNATIONAL) : string
    {
        if (empty($iso)) {
            return $tel;
        }

        try {
            $phoneUtil = PhoneNumberUtil::getInstance();

            $phoneInstance = $phoneUtil->parse($tel, mb_strtoupper($iso));

            $tel = $phoneUtil->format($phoneInstance, $format);
        } catch (NumberParseException $e) {
            // Do nothing if the number cannot be parsed successfully
        }

        return $tel;
    }
}
