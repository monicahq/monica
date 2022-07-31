<?php

return [

    /*
    |--------------------------------------------------------------------------
    | DEFAULT STORAGE LIMIT IN MB
    |--------------------------------------------------------------------------
    |
    | This value represents the default storage limit for every new account in
    | this instance. The value is represented in MB.
    |
    */

    'default_storage_limit_in_mb' => env('DEFAULT_STORAGE_LIMIT', 50),

    /*
    |--------------------------------------------------------------------------
    | URL of the documentation center
    |--------------------------------------------------------------------------
    |
    | This platform hosts the help documentation.
    |
    */

    'help_center_url' => 'https://docs-ivory-one.vercel.app/docs/',

    /*
    |--------------------------------------------------------------------------
    | HELP CENTER URL
    |--------------------------------------------------------------------------
    |
    | These are the links that are used in the UI to point to the right help
    | section.
    |
    */

    'help_links' => [
        'last_updated_contacts' => 'vaults/dashboard#last-updated-contacts',
    ],
];
