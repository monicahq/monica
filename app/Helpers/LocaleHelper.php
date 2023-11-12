<?php

namespace App\Helpers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Matriphe\ISO639\ISO639;

use function Safe\preg_match;
use function Safe\preg_split;

class LocaleHelper
{
    private const LANG_SPLIT = '/(-|_)/';

    /**
     * Get the current country from locale.
     *
     * @return string country, uppercase form.
     */
    public static function getCountry($locale = null)
    {
        $countryCode = self::extractCountry($locale);

        if (is_null($countryCode)) {
            $country = CountriesHelper::getCountryFromLocale($locale);
            $countryCode = $country->getIsoAlpha2();
        }

        return mb_strtoupper($countryCode);
    }

    /**
     * Extract the current country from locale, i.e. 'en-US' will return 'US'.
     * If no country is present in the locale, it will return null.
     *
     * @return string|null country, uppercase form.
     */
    public static function extractCountry($locale = null): ?string
    {
        if (is_null($locale)) {
            $locale = App::getLocale();
        }
        if (preg_match(self::LANG_SPLIT, $locale)) {
            $locale = preg_split(self::LANG_SPLIT, $locale, 2)[1];

            return mb_strtoupper($locale);
        }

        return null;
    }

    /**
     * Association ISO-639-1 => ISO-639-2.
     *
     * @var array<string,string>
     */
    private static $locales = [];

    /**
     * Get ISO-639-2/t (three-letter codes) from ISO-639-1 (two-letters code).
     */
    public static function getLocaleAlpha(string $locale): string
    {
        if (Arr::has(static::$locales, $locale)) {
            return Arr::get(static::$locales, $locale);
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
