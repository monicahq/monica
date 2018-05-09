<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use libphonenumber\PhoneNumberFormat;

class LocaleHelper
{
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

        return $locales->sortBy('name');
    }

    /**
     * Get the direction: left to right/right to left.
     *
     * @return string
     */
    public static function getDirection()
    {
        $locale = self::getLocale();

        switch ($locale) {
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
     * Format phone number by country
     *
     * @param string $tel
     * @param $iso
     * @param int $format
     *
     * @return null | string
     */
    public static function formatTelephoneNumberByISO(string $tel, $iso, int $format = PhoneNumberFormat::INTERNATIONAL)
    {
        if (empty($iso)) {
            return $tel;
        }

        try {
            $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();

            $phoneInstance = $phoneUtil->parse($tel, strtoupper($iso));

            $tel = $phoneUtil->format($phoneInstance, $format);
        } catch (\libphonenumber\NumberParseException $e) {
            // Do nothing if the number cannot be parsed successfully
        }

        return $tel;
    }
}
