<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sentry dsn
    |--------------------------------------------------------------------------
    |
    | Do not put your secret!
    | See https://sentry.io/settings/{slug}/{project}/keys/
    |
    */
    'dsn' => env('SENTRY_LARAVEL_DSN'),

    /*
    |--------------------------------------------------------------------------
    | Release number
    |--------------------------------------------------------------------------
    |
    | Stored in .sentry-release file, or get from git commit number
    |
    */
    'release' => is_file(__DIR__.'/../.sentry-release') ? file_get_contents(__DIR__.'/../.sentry-release') : (is_dir(__DIR__.'/../.git') ? trim(exec('git log --pretty="%h" -n1 HEAD')) : null),

    /*
    |--------------------------------------------------------------------------
    | Capture bindings on SQL queries
    |--------------------------------------------------------------------------
    */
    'breadcrumbs.sql_bindings' => true,

    /*
    |--------------------------------------------------------------------------
    | Capture default user context
    |--------------------------------------------------------------------------
    */
    'send_default_pii' => true,
];
