<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Limit per page
     |--------------------------------------------------------------------------
     |
     | This is the maximum number of items the API can return per page.
     |
     */
     'max_limit_per_page' => env('MAX_API_LIMIT_PER_PAGE', 100),

    /*
    |--------------------------------------------------------------------------
    | Error codes for the API
    |--------------------------------------------------------------------------
    */
    'error_codes' => [
        '30' => 'The limit parameter is too big.',
        '31' => 'The resource has not been found.',
        '32' => 'Invalid data necessary to create the entity.'
    ],
];
