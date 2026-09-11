<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SMS "From" Number
    |--------------------------------------------------------------------------
    |
    | This configuration option defines the phone number that will be used as
    | the "from" number for all outgoing text messages. You should provide
    | the number you have already reserved within your Vonage dashboard.
    |
    */

    'sms_from' => env('VONAGE_SMS_FROM'),

    /*
    |--------------------------------------------------------------------------
    | API Credentials
    |--------------------------------------------------------------------------
    |
    | The following configuration options contain your API credentials, which
    | may be accessed from your Vonage dashboard. These credentials may be
    | used to authenticate with the Vonage API so you may send messages.
    |
    */

    'api_key' => env('VONAGE_KEY'),

    'api_secret' => env('VONAGE_SECRET'),

    'application_id' => env('VONAGE_APPLICATION_ID'),

    /*
    |--------------------------------------------------------------------------
    | Signature Secret
    |--------------------------------------------------------------------------
    |
    | If your application is receiving webhooks from Vonage, you may wish to
    | configure a message signature secret so that you can ensure each of
    | the inbound webhook calls are actually originating within Vonage.
    |
    */

    'signature_secret' => env('VONAGE_SIGNATURE_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Private Key
    |--------------------------------------------------------------------------
    |
    | Some of Vonage's recent APIs utilize JWTs for authentication, which also
    | require a private key so that they may be signed. You may define your
    | application's private key string below via the configuration value.
    |
    */

    'private_key' => env('VONAGE_PRIVATE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Application Identifiers
    |--------------------------------------------------------------------------
    |
    | Adding an application name and version may assist you in identifying
    | problems with your application or when viewing analytics for your
    | application's API usage within the dedicated Vonage dashboards.
    |
    */

    'app' => [
        'name' => env('VONAGE_APP_NAME', 'Laravel'),
        'version' => env('VONAGE_APP_VERSION', '1.1.2'),
    ],

    'http_client' => \GuzzleHttp\Client::class,

];
