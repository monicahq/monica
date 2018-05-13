<?php

namespace App\Helpers;

use Matriphe\ISO639\ISO639;
use Illuminate\Support\Facades\Auth;

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

        return CollectionHelper::sortByCollator($locales, 'name');
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
     * Association ISO-639-1 => ISO-639-2.
     */
    private static $locales = [];

    /**
     * Get ISO-639-2/t (three-letter codes) from ISO-639-1 (two-letters code).
     *
     * @param string
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
}
