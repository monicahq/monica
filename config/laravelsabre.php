<?php

declare(strict_types=1);

use App\Http\Middleware\AuthenticateWithApiKeyOnBasicAuth;
use App\Http\Middleware\SetApiLocale;
use App\Http\Middleware\StartSessionInLocalEnvironment;
use Laravel\Nightwatch\Http\Middleware\Sample;

return [

    /*
    |--------------------------------------------------------------------------
    | LaravelSabre Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where LaravelSabre will be accessible from. If the
    | setting is null, LaravelSabre will reside under the same domain as the
    | application. Otherwise, this value will be used as the subdomain.
    |
    */

    'domain' => null,

    /*
    |--------------------------------------------------------------------------
    | LaravelSabre Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where LaravelSabre will be accessible from. Feel free
    | to change this path to anything you like. The DAV href values in every
    | response follow this setting.
    |
    */

    'path' => 'dav',

    /*
    |--------------------------------------------------------------------------
    | LaravelSabre Master Switch
    |--------------------------------------------------------------------------
    |
    | This option may be used to disable LaravelSabre. Every request under the
    | path above is then answered with 404, before any node, plugin or provider
    | is touched.
    |
    */

    'enabled' => env('LARAVELSABRE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | LaravelSabre Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every LaravelSabre route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */

    'middleware' => [
        StartSessionInLocalEnvironment::class,
        AuthenticateWithApiKeyOnBasicAuth::class,
        Sample::rate(env('NIGHTWATCH_FEATURE_SAMPLE_RATE', env('NIGHTWATCH_REQUEST_SAMPLE_RATE', 1.0))),
        'flag:carddav',
        SetApiLocale::class,
        'throttle:api',
    ],

    /*
    |--------------------------------------------------------------------------
    | LaravelSabre HTTP Methods
    |--------------------------------------------------------------------------
    |
    | The HTTP methods the DAV endpoint accepts. Add a method here to have it
    | routed to the DAV server; handling it is then up to the server and its
    | plugins. Removing a method makes the endpoint answer it with 405.
    |
    */

    'methods' => [
        'GET',
        'HEAD',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'OPTIONS',
        'PROPFIND',
        'PROPPATCH',
        'MKCOL',
        'MKCALENDAR',
        'COPY',
        'MOVE',
        'LOCK',
        'UNLOCK',
        'REPORT',
        'ACL',
    ],

    /*
    |--------------------------------------------------------------------------
    | LaravelSabre Authentication Guard
    |--------------------------------------------------------------------------
    |
    | The guard used to resolve the currently signed in user. When this is null,
    | the application's default guard is used.
    |
    */

    'guard' => 'sanctum',

    /*
    |--------------------------------------------------------------------------
    | LaravelSabre Authentication Realm
    |--------------------------------------------------------------------------
    |
    | The realm named in the authentication challenge sent to a client that has
    | not authenticated yet.
    |
    */

    'realm' => env('APP_NAME', 'Monica'),

    /*
    |--------------------------------------------------------------------------
    | LaravelSabre Principal Attribute
    |--------------------------------------------------------------------------
    |
    | The user attribute mapped into the DAV principal identifier, used when no
    | mapping closure is registered with LaravelSabre::principal(). The
    | resulting principal is "principals/{value of this attribute}".
    |
    */

    'principal_attribute' => 'email',

];
