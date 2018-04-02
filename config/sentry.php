<?php

return [
    'dsn' => env('SENTRY_DSN'),

    // capture release as git sha
    'release' => is_dir(__DIR__.'/../.git') ? trim(exec('git log --pretty="%h" -n1 HEAD')) : null,

    // Capture bindings on SQL queries
    'breadcrumbs.sql_bindings' => true,
];
