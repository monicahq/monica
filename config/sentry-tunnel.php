<?php

declare(strict_types=1);

use Illuminate\Support\Str;

return [
    'tunnel-url' => env('SENTRY_TUNNEL_URL', '/sentry/tunnel'),

    // this is required to prevent misuse
    'allowed-hosts' => env('SENTRY_TUNNEL_ALLOWED_HOSTS', Str::of(env('SENTRY_LARAVEL_DSN'))->after('@')->before('/')->__toString()),

    // this is optional, all project are allowed by default
    'allowed-projects' => env('SENTRY_TUNNEL_ALLOWED_PROJECTS', Str::of(env('SENTRY_LARAVEL_DSN'))->afterLast('/')->__toString()),

    /*
     * see the readme before disabling this
     * is only relevant when you use the provided MiddlewareList
     */
    'use-auth-middleware' => env('SENTRY_TUNNEL_USE_AUTH_MIDDLEWARE', true),
];
