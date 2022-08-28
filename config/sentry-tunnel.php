<?php

declare(strict_types=1);

return [
    'tunnel-url' => env('SENTRY_TUNNEL_URL', '/sentry/tunnel'),

    // this is required to prevent misuse
    'allowed-hosts' => env('SENTRY_TUNNEL_ALLOWED_HOSTS'),

    // this is optional, all project are allowed by default
    'allowed-projects' => env('SENTRY_TUNNEL_ALLOWED_PROJECTS'),

    /*
     * see the readme before disabling this
     * is only relevant when you use the provided MiddlewareList
     */
    'use-auth-middleware' => env('SENTRY_TUNNEL_USE_AUTH_MIDDLEWARE', true),
];
