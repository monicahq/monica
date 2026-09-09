<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Turnstile
    |--------------------------------------------------------------------------
    |
    | Whether the guest forms carry a Cloudflare Turnstile challenge. It is off
    | by default, so an instance runs without a Cloudflare account, and the
    | widget renders nothing at all until both keys are filled in.
    |
    */

    'enabled' => env('TURNSTILE_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Site key
    |--------------------------------------------------------------------------
    |
    | The public key of the widget, given by Cloudflare. It is printed in the
    | page, so it is not a secret, and it identifies which challenge the
    | browser has to solve.
    |
    */

    'site_key' => env('TURNSTILE_SITE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Secret key
    |--------------------------------------------------------------------------
    |
    | The private key used to check the token the browser sends back. It never
    | leaves the server, and Cloudflare rejects the verification call without
    | it, so a missing key fails the challenge rather than skipping it.
    |
    */

    'secret_key' => env('TURNSTILE_SECRET_KEY'),

];
