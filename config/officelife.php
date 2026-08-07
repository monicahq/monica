<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Supported locales
    |--------------------------------------------------------------------------
    |
    | The languages the interface is translated into. The key is the locale, and
    | matches the name of the file in the lang folder. The flag is a css value
    | for the background property, drawn rather than shipped as an image.
    |
    */

    'locales' => [
        'en' => [
            'label' => 'English',
            'region' => 'United Kingdom',
            'flag' => 'linear-gradient(to bottom, transparent 40%, #C8102E 40% 60%, transparent 60%), linear-gradient(to right, transparent 38%, #C8102E 38% 62%, transparent 62%), linear-gradient(to bottom, transparent 30%, #fff 30% 70%, transparent 70%), linear-gradient(to right, transparent 28%, #fff 28% 72%, transparent 72%), linear-gradient(to bottom right, transparent 44%, #fff 44% 56%, transparent 56%), linear-gradient(to bottom left, transparent 44%, #fff 44% 56%, transparent 56%), #012169',
        ],
        'fr_FR' => [
            'label' => 'Français',
            'region' => 'France',
            'flag' => 'linear-gradient(to right, #002395 0 33%, #fff 33% 66%, #ed2939 66%)',
        ],
        'de_DE' => [
            'label' => 'Deutsch',
            'region' => 'Deutschland',
            'flag' => 'linear-gradient(to bottom, #000 0 33%, #dd0000 33% 66%, #ffce00 66%)',
        ],
        'es_ES' => [
            'label' => 'Español',
            'region' => 'España',
            'flag' => 'linear-gradient(to bottom, #aa151b 0 25%, #f1bf00 25% 75%, #aa151b 75%)',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Remember me
    |--------------------------------------------------------------------------
    |
    | How long a session survives when somebody ticks "remember me" on the sign
    | in screen. The screen names this number, so changing it here changes what
    | the visitor is promised.
    |
    */

    'remember_duration_days' => env('OFFICELIFE_REMEMBER_DURATION_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Magic link
    |--------------------------------------------------------------------------
    |
    | How many minutes a passwordless sign in link stays valid. It is short on
    | purpose: the link is single use, but a short window limits the damage of
    | a forwarded or intercepted email.
    |
    */

    'magic_link_duration_minutes' => env('OFFICELIFE_MAGIC_LINK_DURATION_MINUTES', 5),

    /*
    |--------------------------------------------------------------------------
    | Legal pages
    |--------------------------------------------------------------------------
    |
    | Where the terms of use and the privacy policy live. They sit on the public
    | site rather than in the application, so an instance that runs its own copy
    | of those documents can point at them here.
    |
    */

    'terms_url' => env('OFFICELIFE_TERMS_URL', 'https://officelife.io/terms'),

    'privacy_url' => env('OFFICELIFE_PRIVACY_URL', 'https://officelife.io/privacy'),

];
