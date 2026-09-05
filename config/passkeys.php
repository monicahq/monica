<?php

declare(strict_types=1);

return [

    'relying_party_id' => parse_url(config('app.url'), PHP_URL_HOST),

    'allowed_origins' => [
        config('app.url'),
    ],

    'user_handle_secret' => env('PASSKEYS_USER_HANDLE_SECRET', config('app.key')),

    'timeout' => 60000,

    'guard' => 'web',

    'middleware' => ['web'],

    'management_middleware' => [],

    'throttle' => 'throttle:passkey',

    'redirect' => '/',

];
