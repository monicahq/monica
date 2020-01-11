<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ipstack Api key
    |--------------------------------------------------------------------------
    |
    | Get your ipstack apikey here https://ipstack.com/dashboard
    |
    */
    'ipstack_apikey' => env('IPSTACK_APIKEY', null),

    /*
    |--------------------------------------------------------------------------
    | Driver
    |--------------------------------------------------------------------------
    |
    | The default driver you would like to use for location retrieval.
    |
    */
    'driver' => App\Http\Location\Drivers\CloudflareDriver::class,

    /*
    |--------------------------------------------------------------------------
    | Driver Fallbacks
    |--------------------------------------------------------------------------
    |
    | The drivers you want to use to retrieve the users location
    | if the above selected driver is unavailable.
    |
    | These will be called upon in order (first to last).
    |
    */
    'fallbacks' => [
        Stevebauman\Location\Drivers\IpInfo::class,
        Stevebauman\Location\Drivers\GeoPlugin::class,
        Stevebauman\Location\Drivers\MaxMind::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | MaxMind Configuration
    |--------------------------------------------------------------------------
    |
    | The configuration for the MaxMind driver.
    |
    | If web service is enabled, you must fill in your user ID and license key.
    |
    | If web service is disabled, it will try and retrieve the users location
    | from the MaxMind database file located in the local path below.
    |
    */
    'maxmind' => [
        'web' => [
            'enabled' => false,
            'user_id' => '',
            'license_key' => '',
            'options' => [
                'host' => 'geoip.maxmind.com',
            ],
        ],
        'local' => [
            'path' => database_path('maxmind/GeoLite2-City.mmdb'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Localhost Testing
    |--------------------------------------------------------------------------
    |
    | If your running your website locally and want to test different
    | IP addresses to see location detection, set 'enabled' to true.
    |
    | The testing IP address is a Google host in the United-States.
    |
    */
    'testing' => [
        'enabled' => false,
        'ip' => '66.102.0.0',
    ],

    /*
    |--------------------------------------------------------------------------
    | Locationiq API Url
    |--------------------------------------------------------------------------
    |
    | Url to call Locationiq api. See https://locationiq.com/docs
    |
    */
    'location_iq_url' => env('LOCATIONIQ_URL', 'https://us1.locationiq.com/v1/'),

    /*
    |--------------------------------------------------------------------------
    | Darksky API Url
    |--------------------------------------------------------------------------
    |
    | Url to call Darksy api. See https://darksky.net/dev/docs
    |
    */
    'darksky_url' => env('DARKSKY_URL', 'https://api.darksky.net/forecast/'),

];
