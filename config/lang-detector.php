<?php

return [
    /*
     * Indicates whenever should autodetect and apply the language of the request.
     */
    'autodetect' => env('LANG_DETECTOR_AUTODETECT', false),

    /*
     * Default driver to use to detect the request language.
     *
     * Available: browser, subdomain, uri.
     */
    'driver' => env('LANG_DETECTOR_DRIVER', 'browser'),

    /*
     * Used on subdomain and uri drivers. That indicates which segment should be used
     * to verify the language.
     */
    'segment' => env('LANG_DETECTOR_SEGMENT', 0),

    /*
     * Languages available on the application.
     *
     * You could use parse_langs_to_array to use the string syntax
     * or just use the array of languages with its aliases.
     */
    'languages' => parse_langs_to_array(
        env('LANG_DETECTOR_LANGUAGES', [
            'ar',
            'cs',
            'de',
            'en',
            'es',
            'fr',
            'he',
            'hr',
            'it',
            'nl',
            'pt',
            'ru',
            'tr',
            'zh',
        ])
    ),

    /*
     * Indicates if should store detected locale on cookies
     */
    'cookie' => (bool) env('LANG_DETECTOR_COOKIE', true),

    /*
     * Indicates if should encrypt cookie
     */
    'cookie_encrypt' => (bool) env('LANG_DETECTOR_COOKIE_ENCRYPT', false),

    /*
     * Cookie name
     */
    'cookie_name' => env('LANG_DETECTOR_COOKIE', 'locale'),
];
