<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Driver prefix
    |--------------------------------------------------------------------------
    |
    | Empty, so a connection is registered under its own name. That name is the
    | URL segment, the stored provider and the services.* key at once.
    |
    */

    'driver_prefix' => '',

    /*
    |--------------------------------------------------------------------------
    | Connections
    |--------------------------------------------------------------------------
    |
    | One entry per provider offered by name. The package copies each one to
    | services.{name} when it boots, which is where the application reads it.
    | The generic provider is the openidconnect block of config/services.php.
    |
    */

    'connections' => [

        'entra' => [
            'provider' => 'entra',
            'tenant' => env('ENTRA_TENANT', 'common'),
            'client_id' => env('ENTRA_CLIENT_ID'),
            'client_secret' => env('ENTRA_CLIENT_SECRET'),
            'redirect' => env('ENTRA_REDIRECT_URI', '/sso/entra/callback'),
        ],

        'keycloak' => [
            'provider' => 'keycloak',
            'server_url' => env('KEYCLOAK_SERVER_URL'),
            'realm' => env('KEYCLOAK_REALM'),
            'client_id' => env('KEYCLOAK_CLIENT_ID'),
            'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),
            'redirect' => env('KEYCLOAK_REDIRECT_URI', '/sso/keycloak/callback'),
        ],

        'auth0' => [
            'provider' => 'auth0',
            'domain' => env('AUTH0_DOMAIN'),
            'client_id' => env('AUTH0_CLIENT_ID'),
            'client_secret' => env('AUTH0_CLIENT_SECRET'),
            'redirect' => env('AUTH0_REDIRECT_URI', '/sso/auth0/callback'),
        ],

        'okta' => [
            'provider' => 'okta',
            'domain' => env('OKTA_DOMAIN'),
            'auth_server' => env('OKTA_AUTH_SERVER'),
            'client_id' => env('OKTA_CLIENT_ID'),
            'client_secret' => env('OKTA_CLIENT_SECRET'),
            'redirect' => env('OKTA_REDIRECT_URI', '/sso/okta/callback'),
        ],

    ],

];
