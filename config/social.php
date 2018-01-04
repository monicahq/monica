<?php

return [
    'services' => [
        'facebook' => [
            'name' => 'Facebook',
            'allow_signup' => env('APP_ALLOW_GOOGLE_SIGNUP', false),
        ],
        'google' => [
            'name' => 'Google',
            'allow_signup' => env('APP_ALLOW_FACEBOOK_SIGNUP', false),
        ],
    ],
];