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
    | Position
    |--------------------------------------------------------------------------
    |
    | Here you may configure the position instance that is created
    | and returned from the above drivers. The instance you
    | create must extend the built-in Position class.
    |
    */
    'position' => Stevebauman\Location\Position::class,

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
    | IP API Pro Configuration
    |--------------------------------------------------------------------------
    |
    | The configuration for the IP API Pro driver.
    |
    */
    'ip_api' => [
        'token' => env('IP_API_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | IPInfo Configuration
    |--------------------------------------------------------------------------
    |
    | The configuration for the IPInfo driver.
    |
    */
    'ipinfo' => [
        'token' => env('IPINFO_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | IPData Configuration
    |--------------------------------------------------------------------------
    |
    | The configuration for the IPData driver.
    |
    */
    'ipdata' => [
        'token' => env('IPDATA_TOKEN'),
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
        'enabled' => env('LOCATION_TESTING', false),
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
    | Weatherapi Url
    |--------------------------------------------------------------------------
    |
    | Url to call Weatherapi.
    |
    */
    'weatherapi_url' => env('WEATHERAPI_URL', 'https://api.weatherapi.com/v1/current.json'),

];
