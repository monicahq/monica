/**
  *  This file is part of Monica.
  *
  *  Monica is free software: you can redistribute it and/or modify
  *  it under the terms of the GNU Affero General Public License as published by
  *  the Free Software Foundation, either version 3 of the License, or
  *  (at your option) any later version.
  *
  *  Monica is distributed in the hope that it will be useful,
  *  but WITHOUT ANY WARRANTY; without even the implied warranty of
  *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  *  GNU Affero General Public License for more details.
  *
  *  You should have received a copy of the GNU Affero General Public License
  *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
  **/


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
        'enabled' => true,
        'ip' => '66.102.0.0',
    ],
];
