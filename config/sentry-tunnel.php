<?php

use Illuminate\Support\Str;

return [
    /*
    |--------------------------------------------------------------------------
    | Allowed Hosts
    |--------------------------------------------------------------------------
    |
    | This is the list of target sentry hosts that are allowed to use the tunnel.
    | It uses the value of 'SENTRY_LARAVEL_DSN' by default.
    |
    */

    'allowed-hosts' => explode(',', env('SENTRY_TUNNEL_ALLOWED_HOSTS', (string) Str::of(env('SENTRY_LARAVEL_DSN'))->after('@')->before('/'))),

    /*
    |--------------------------------------------------------------------------
    | Allowed Projects
    |--------------------------------------------------------------------------
    |
    | This is the list of target sentry projects that are allowed to use the tunnel.
    | It uses the value of 'SENTRY_LARAVEL_DSN' by default.
    | If the value is empty, all projects are allowed. Otherwise, it should be a
    | comma-separated list of project IDs.
    */

    'allowed-projects' => explode(',', env('SENTRY_TUNNEL_ALLOWED_PROJECTS', (string) Str::of(env('SENTRY_LARAVEL_DSN'))->afterLast('/'))),

    /*
    |--------------------------------------------------------------------------
    | Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where the tunnel will be accessible from. If the
    | setting is null, the route will reside under the same domain as the
    | application. Otherwise, this value will be used as the subdomain.
    |
    */

    'domain' => null,

    /*
    |--------------------------------------------------------------------------
    | Tunnel url
    |--------------------------------------------------------------------------
    |
    | This is the URI path of the tunnel. It is used to define the route that
    | will be used to proxy the requests to Sentry.
    |
    */

    'tunnel-url' => env('SENTRY_TUNNEL_URL', '/sentry/tunnel'),

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middlewares will be assigned to the tunnel route.
    |
    */

    'middleware' => [
        'web',
    ],
];
