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
    | for the background property, drawn rather than shipped as an image. The
    | date format is the order that language reads dates in, used for whoever
    | has not picked one of their own.
    |
    */

    'locales' => [
        'en' => [
            'label' => 'English',
            'region' => 'United Kingdom',
            'date_format' => 'd/m/Y',
            'flag' => 'linear-gradient(to bottom, transparent 40%, #C8102E 40% 60%, transparent 60%), linear-gradient(to right, transparent 38%, #C8102E 38% 62%, transparent 62%), linear-gradient(to bottom, transparent 30%, #fff 30% 70%, transparent 70%), linear-gradient(to right, transparent 28%, #fff 28% 72%, transparent 72%), linear-gradient(to bottom right, transparent 44%, #fff 44% 56%, transparent 56%), linear-gradient(to bottom left, transparent 44%, #fff 44% 56%, transparent 56%), #012169',
        ],
        'fr_FR' => [
            'label' => 'Français',
            'region' => 'France',
            'date_format' => 'd/m/Y',
            'flag' => 'linear-gradient(to right, #002395 0 33%, #fff 33% 66%, #ed2939 66%)',
        ],
        'de_DE' => [
            'label' => 'Deutsch',
            'region' => 'Deutschland',
            'date_format' => 'd.m.Y',
            'flag' => 'linear-gradient(to bottom, #000 0 33%, #dd0000 33% 66%, #ffce00 66%)',
        ],
        'es_ES' => [
            'label' => 'Español',
            'region' => 'España',
            'date_format' => 'd/m/Y',
            'flag' => 'linear-gradient(to bottom, #aa151b 0 25%, #f1bf00 25% 75%, #aa151b 75%)',
        ],
        'pt_PT' => [
            'label' => 'Português',
            'region' => 'Portugal',
            'date_format' => 'd/m/Y',
            'flag' => 'linear-gradient(to right, #006600 0 40%, #ff0000 40%)',
        ],
        'nl_NL' => [
            'label' => 'Nederlands',
            'region' => 'Nederland',
            'date_format' => 'd-m-Y',
            'flag' => 'linear-gradient(to bottom, #ae1c28 0 33%, #fff 33% 66%, #21468b 66%)',
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

    'remember_duration_days' => env('MONICA_REMEMBER_DURATION_DAYS', 30),

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

    'magic_link_duration_minutes' => env('MONICA_MAGIC_LINK_DURATION_MINUTES', 5),

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

    'terms_url' => env('MONICA_TERMS_URL', 'https://monicahq.com/terms'),

    'privacy_url' => env('MONICA_PRIVACY_URL', 'https://monicahq.com/privacy'),

    /*
    |--------------------------------------------------------------------------
    | Self hosted
    |--------------------------------------------------------------------------
    |
    | Whether this instance runs outside of the cloud one. An account created on
    | a self hosted instance is never metered: it has no storage ceiling and no
    | rate limit applies to it.
    |
    */

    'self_hosted' => env('MONICA_SELF_HOSTED', false),

    /*
    |--------------------------------------------------------------------------
    | Free storage
    |--------------------------------------------------------------------------
    |
    | How many bytes of files an account gets on the free offer of the cloud
    | instance, before the one time purchase. The ceiling is stored on the
    | account when it is created, so raising this number here does not raise it
    | for the accounts that already exist.
    |
    */

    'free_storage_limit_in_bytes' => env('MONICA_FREE_STORAGE_LIMIT_IN_BYTES', 104857600),

];
