<?php

use Illuminate\Support\Facades\App;

if (! function_exists('trans_key')) {
    /**
     * Extract the message.
     */
    function trans_key(?string $key = null): ?string
    {
        return $key;
    }
}

if (! function_exists('trans_ignore')) {
    /**
     * Translate the given message. It won't be extracted by monica:localize command.
     */
    function trans_ignore(?string $key = null, array $replace = [], ?string $locale = null): string
    {
        return __($key, $replace, $locale);
    }
}

if (! function_exists('currentLang')) {
    /**
     * Get the current language from locale.
     */
    function currentLang(?string $locale = null): string
    {
        if ($locale === null) {
            $locale = App::getLocale();
        }

        if (preg_match('/(-|_)/', $locale)) {
            $locale = preg_split('/(-|_)/', $locale, 2)[0];
        }

        return mb_strtolower($locale);
    }
}

if (! function_exists('htmldir')) {
    /**
     * Get the direction: left to right/right to left.
     */
    function htmldir()
    {
        $lang = currentLang();
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
}
