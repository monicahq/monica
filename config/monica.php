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
    | available on https://monicahq.com for free.
    |
    | Available Settings: true, false
    |
    */

    'access_to_paid_features_for_free' => env('APP_ACCESS_TO_PAID_FEATURES_FOR_FREE', true),
];
