<?php

declare(strict_types=1);

use Spatie\LoginLink\Http\Controllers\LoginLinkController;

return [

    /*
    |--------------------------------------------------------------------------
    | Allowed environments
    |--------------------------------------------------------------------------
    |
    | The environments where a login link signs somebody in without a password.
    | Anywhere else the package throws, so the shortcut stays a convenience
    | for the people who develop the application and never leaves it.
    |
    */

    'allowed_environments' => ['local'],

    /*
    |--------------------------------------------------------------------------
    | Allowed hosts
    |--------------------------------------------------------------------------
    |
    | The hosts the link is allowed to be clicked from, on top of the check on
    | the environment. A production domain that is somehow marked as local
    | still refuses to sign anybody in, as it matches none of these.
    |
    */

    'allowed_hosts' => [
        'localhost',
        '127.0.0.1',
        '*.test',
    ],

    /*
    |--------------------------------------------------------------------------
    | Automatically create missing users
    |--------------------------------------------------------------------------
    |
    | Whether an unknown email address creates a user on the fly. It is off, as
    | a user here belongs to a company and an account made out of thin air
    | would have none, so the link only opens seeded accounts.
    |
    */

    'automatically_create_missing_users' => false,

    /*
    |--------------------------------------------------------------------------
    | User model
    |--------------------------------------------------------------------------
    |
    | The model the link signs in. It is null, which means the package reads the
    | model of the `users` provider in the authentication configuration, so
    | there is a single place that says what an account is.
    |
    */

    'user_model' => null,

    /*
    |--------------------------------------------------------------------------
    | Redirect route name
    |--------------------------------------------------------------------------
    |
    | Where somebody lands once the link signed them in. It is null, so they go
    | to the page they asked for before, or to the url the link carries,
    | which is what the component on the sign in screen passes.
    |
    */

    'redirect_route_name' => null,

    /*
    |--------------------------------------------------------------------------
    | Controller
    |--------------------------------------------------------------------------
    |
    | The controller the package points its route to. It is the one the package
    | ships, as nothing here needs to happen on top of signing the user in.
    |
    */

    'login_link_controller' => LoginLinkController::class,

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | The middleware the route runs through. It needs the session to remember
    | who was signed in, so it goes through the web stack and nothing else.
    |
    */

    'middleware' => ['web'],

];
