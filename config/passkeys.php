<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Relying Party ID
    |--------------------------------------------------------------------------
    |
    | The relying party ID represents your application in the WebAuthn protocol.
    | This is typically your domain (e.g., "example.com"). Passkeys are bound
    | to this ID and can only be verified on matching domains.
    |
    */

    'relying_party_id' => parse_url(config('app.url'), PHP_URL_HOST),

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    |
    | The origins permitted to complete WebAuthn ceremonies. Passkeys bound
    | to the relying party ID above will only verify when the browser
    | reports one of these origins. Defaults to your application URL.
    |
    */

    'allowed_origins' => [
        config('app.url'),
    ],

    /*
    |--------------------------------------------------------------------------
    | User Handle Secret
    |--------------------------------------------------------------------------
    |
    | Secret used to derive a stable WebAuthn user handle from each user model.
    | Set this explicitly if you rotate your application key.
    |
    */

    'user_handle_secret' => env('PASSKEYS_USER_HANDLE_SECRET', config('app.key')),

    /*
    |--------------------------------------------------------------------------
    | WebAuthn Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout in milliseconds for WebAuthn operations. This determines
    | how long users have to complete passkey registration or verification.
    |
    */

    'timeout' => 60000,

    /*
    |--------------------------------------------------------------------------
    | Authentication Guard
    |--------------------------------------------------------------------------
    |
    | The authentication guard to use when logging in users with passkeys.
    | This should match your application's primary authentication guard.
    |
    */

    'guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Passkeys Routes Middleware
    |--------------------------------------------------------------------------
    |
    | Here you may specify which middleware Passkeys will assign to the routes
    | that it registers with the application. If necessary, you may change
    | these middleware but typically this provided default is preferred.
    |
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Passkeys Management Middleware
    |--------------------------------------------------------------------------
    |
    | Here you may specify the middleware applied to passkey management routes
    | that create or delete passkeys. By default, Laravel's password
    | confirmation middleware is used.
    |
    | It is deliberately empty here. An account may hold a passkey and no
    | password at all, and asking such a person to confirm one leaves them
    | unable to add a second passkey, which is the only way back from a lost
    | device. The routes stay behind `auth`.
    |
    */

    'management_middleware' => [],

    /*
    |--------------------------------------------------------------------------
    | Passkeys Throttling
    |--------------------------------------------------------------------------
    |
    | Middleware used to throttle passkey endpoints. Set to null to disable.
    |
    | The shipped `throttle:6,1` keys on the address alone, so everybody behind
    | one address would share a single allowance. The named limiter, defined in
    | AppServiceProvider, keys on the credential instead.
    |
    */

    'throttle' => 'throttle:passkey',

    /*
    |--------------------------------------------------------------------------
    | Redirect
    |--------------------------------------------------------------------------
    |
    | The path to redirect to after successful passkey verification.
    |
    */

    'redirect' => '/',

];
