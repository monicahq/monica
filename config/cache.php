<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

$config = [

    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default cache store that will be used by the
    | framework. This connection is utilized if another isn't explicitly
    | specified when running a cache operation inside the application.
    |
    */

    'default' => env('CACHE_STORE', env('CACHE_DRIVER', 'database')),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the cache "stores" for your application as
    | well as their drivers. You may even define multiple stores for the
    | same cache driver to group types of items stored in your caches.
    |
    | Supported drivers: "apc", "array", "database", "file", "memcached",
    |                    "redis", "dynamodb", "octane", "null"
    |
    */

    'stores' => [

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver' => 'database',
            'table' => env('DB_CACHE_TABLE', 'cache'),
            'connection' => env('DB_CACHE_CONNECTION'),
            'lock_connection' => env('DB_CACHE_LOCK_CONNECTION'),
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
            'lock_connection' => env('REDIS_CACHE_LOCK_CONNECTION', 'default'),
        ],

        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ],

        'octane' => [
            'driver' => 'octane',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | When utilizing the APC, database, memcached, Redis, and DynamoDB cache
    | stores, there might be other applications using the same cache. For
    | that reason, you may prefix every cache key to avoid collisions.
    |
    */

    'prefix' => env('CACHE_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_cache_'),

];

// on fortrabbit: construct credentials from App secrets
if (env('APP_SECRETS')) {
    $secrets = json_decode(file_get_contents(env('APP_SECRETS')), true);
    if (array_key_exists('MEMCACHE', $secrets)) {
        $servers = [[
            'host' => $secrets['MEMCACHE']['HOST1'],
            'port' => $secrets['MEMCACHE']['PORT1'],
            'weight' => 100,
        ]];
        if ($secrets['MEMCACHE']['COUNT'] > 1) {
            $servers[] = [
                'host' => $secrets['MEMCACHE']['HOST2'],
                'port' => $secrets['MEMCACHE']['PORT2'],
                'weight' => 100,
            ];
        }
        Arr::set($config, 'stores.memcached.servers', $servers);
    }
}

if (extension_loaded('memcached')) {
    $timeout_ms = 50;
    $options = [
        // Assure that dead servers are properly removed and ...
        \Memcached::OPT_REMOVE_FAILED_SERVERS => true,

        // ... retried after a short while (here: 2 seconds)
        \Memcached::OPT_RETRY_TIMEOUT => 2,

        // KETAMA must be enabled so that replication can be used
        \Memcached::OPT_LIBKETAMA_COMPATIBLE => true,

        // Replicate the data, write it to both memcached servers
        \Memcached::OPT_NUMBER_OF_REPLICAS => 1,

        // Those values assure that a dead (due to increased latency or
        // really unresponsive) memcached server is dropped fast
        \Memcached::OPT_POLL_TIMEOUT => $timeout_ms,        // milliseconds
        \Memcached::OPT_SEND_TIMEOUT => $timeout_ms * 1000, // microseconds
        \Memcached::OPT_RECV_TIMEOUT => $timeout_ms * 1000, // microseconds
        \Memcached::OPT_CONNECT_TIMEOUT => $timeout_ms,     // milliseconds

        // Further performance tuning
        \Memcached::OPT_NO_BLOCK => true,
    ];
    Arr::set($config, 'stores.memcached.options', $options);
}

return $config;
