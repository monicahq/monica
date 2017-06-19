<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Locales supported by the application
    |--------------------------------------------------------------------------
    |
    | This is the list of locales that we will use to localize the application.
    | Each locale is defined in `resources/lang/` in their respective folder.
    |
    */
    'langs' => [
        'en',
        'fr',
        'pt-br',
        'ru',
    ],

    /*
    |--------------------------------------------------------------------------
    | Access to paid features
    |--------------------------------------------------------------------------
    |
    | This value determines if the instance can access the paid features that
    | are available on https://monicahq.com, for free.
    | If set to false, the instance won't have access to the paid features.
    |
    | Available Settings: true, false
    |
    */

    'unlock_paid_features' => env('UNLOCK_PAID_FEATURES', true),
];
