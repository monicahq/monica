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

    public static function get($iso)
    {
        $country = Countries::where('cca2', mb_strtoupper($iso))->first();
        if ($country->count() === 0) {
            $country = Countries::where('alt_spellings', mb_strtoupper($iso))->first();
        }
        if ($country->count() === 0) {
            return '';
        }

        return static::getCommonNameLocale($country);
    }

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
}
