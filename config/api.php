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
     'limit_per_page' => env('API_LIMIT_PER_PAGE', 100),

    /*
    |--------------------------------------------------------------------------
    | Error codes for the API
    |--------------------------------------------------------------------------
    */
    'error_codes' => [
        '30' => 'The limit parameter is too big.',
    ],
];
