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

];

// Use fortrabbit secrets
if (isset($_SERVER['APP_SECRETS'])) {
    $secrets = json_decode(file_get_contents($_SERVER['APP_SECRETS']), true);

    if (isset($secrets['CUSTOM']['PASSPORT_PRIVATE_KEY'])) {
        $passports['private_key'] = str_replace('\\\\n', '\\n', $secrets['CUSTOM']['PASSPORT_PRIVATE_KEY']);
    }
    if (isset($secrets['CUSTOM']['PASSPORT_PUBLIC_KEY'])) {
        $passports['public_key'] = str_replace('\\\\n', '\\n', $secrets['CUSTOM']['PASSPORT_PUBLIC_KEY']);
    }
}

return $passports;
