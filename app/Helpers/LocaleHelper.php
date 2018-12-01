/**
  *  This file is part of Monica.
  *
  *  Monica is free software: you can redistribute it and/or modify
  *  it under the terms of the GNU Affero General Public License as published by
  *  the Free Software Foundation, either version 3 of the License, or
  *  (at your option) any later version.
  *
  *  Monica is distributed in the hope that it will be useful,
  *  but WITHOUT ANY WARRANTY; without even the implied warranty of
  *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  *  GNU Affero General Public License for more details.
  *
  *  You should have received a copy of the GNU Affero General Public License
  *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
  **/


namespace App\Helpers;

use Matriphe\ISO639\ISO639;
use Illuminate\Support\Facades\Auth;
use libphonenumber\PhoneNumberFormat;

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
            $locale = self::getLocale();
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
        if (is_null($locale)) {
            $locale = self::getLocale();
        }
        if (preg_match(self::LANG_SPLIT, $locale)) {
            $locale = preg_split(self::LANG_SPLIT, $locale, 2)[1];
        } else {
            $country = CountriesHelper::getCountryFromLocale($locale);
            $locale = $country->cca2;
        }

        return mb_strtoupper($locale);
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

    /**
     * Format phone number by country.
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
