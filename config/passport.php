<?php

use function Safe\json_decode;

$passports = [

    /*
    |--------------------------------------------------------------------------
    | Encryption Keys
    |--------------------------------------------------------------------------
    |
    | Passport uses encryption keys while generating secure access tokens for
    | your application. By default, the keys are stored as local files but
    | can be set via environment variables when that is more convenient.
    |
    */

    'private_key' => env('PASSPORT_PRIVATE_KEY'),

    'public_key' => env('PASSPORT_PUBLIC_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Client UUIDs
    |--------------------------------------------------------------------------
    |
    | By default, Passport uses auto-incrementing primary keys when assigning
    | IDs to clients. However, if Passport is installed using the provided
    | --uuids switch, this will be set to "true" and UUIDs will be used.
    |
    */

    'client_uuids' => false,

    /*
    |--------------------------------------------------------------------------
    | Personal Access Client
    |--------------------------------------------------------------------------
    |
    | If you enable client hashing, you should set the personal access client
    | ID and unhashed secret within your environment file. The values will
    | get used while issuing fresh personal access tokens to your users.
    |
    */

    'personal_access_client' => [
        'id' => env('PASSPORT_PERSONAL_ACCESS_CLIENT_ID'),
        'secret' => env('PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Grant Client
    |--------------------------------------------------------------------------
    |
    | Password grant client used for oauth login, and mobile application.
    |
    */

    'password_grant_client' => [
        'id' => env('PASSPORT_PASSWORD_GRANT_CLIENT_ID', env('MOBILE_CLIENT_ID')),
        'secret' => env('PASSPORT_PASSWORD_GRANT_CLIENT_SECRET', env('MOBILE_CLIENT_SECRET')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Passport Storage Driver
    |--------------------------------------------------------------------------
    |
    | This configuration value allows you to customize the storage options
    | for Passport, such as the database connection that should be used
    | by Passport's internal database models which store tokens, etc.
    |
    */

    'storage' => [
        'database' => [
            'connection' => env('DB_CONNECTION', 'mysql'),
        ],
    ],

];

// Use fortrabbit secrets
if (env('APP_SECRETS')) {
    $secrets = json_decode(file_get_contents(env('APP_SECRETS')), true);

    if (isset($secrets['CUSTOM']['PASSPORT_PRIVATE_KEY'])) {
        $passports['private_key'] = str_replace('\\\\n', '\\n', $secrets['CUSTOM']['PASSPORT_PRIVATE_KEY']);
    }
    if (isset($secrets['CUSTOM']['PASSPORT_PUBLIC_KEY'])) {
        $passports['public_key'] = str_replace('\\\\n', '\\n', $secrets['CUSTOM']['PASSPORT_PUBLIC_KEY']);
    }
}

return $passports;
