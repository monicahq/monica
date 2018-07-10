<?php

$db = [

    /*
    |--------------------------------------------------------------------------
    | PDO Fetch Style
    |--------------------------------------------------------------------------
    |
    | By default, database results will be returned as instances of the PHP
    | stdClass object; however, you may desire to retrieve records in an
    | array format for simplicity. Here you can tweak the fetch style.
    |
    */

    'fetch' => PDO::FETCH_CLASS,

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Use utf8mb4 charset format
    |--------------------------------------------------------------------------
    |
    | Use the new utf8mb4 charset format
    | ⚠ be sure your DBMS supports utf8mb4 format
    | See https://dev.mysql.com/doc/refman/5.5/en/charset-unicode-utf8mb4.html
    | MySQL > 5.7.7 fully support it.
    |
    */

    'use_utf8mb4' => env('DB_USE_UTF8MB4', true),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => env('DB_PREFIX', ''),
        ],

        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '3306'),
            'unix_socket' => env('DB_UNIX_SOCKET', ''),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_USE_UTF8MB4', true) ? 'utf8mb4' : 'utf8',
            'collation' => env('DB_USE_UTF8MB4', true) ? 'utf8mb4_unicode_ci' : 'utf8_unicode_ci',
            'prefix' => env('DB_PREFIX', ''),
            'strict' => false,
            'engine' => null,
        ],

        'testing' => [
            'driver' => 'mysql',
            'host' => env('DB_TEST_HOST'),
            'unix_socket' => env('DB_TEST_UNIX_SOCKET', ''),
            'database' => env('DB_TEST_DATABASE'),
            'username' => env('DB_TEST_USERNAME'),
            'password' => env('DB_TEST_PASSWORD'),
            'charset' => env('DB_USE_UTF8MB4', true) ? 'utf8mb4' : 'utf8',
            'collation' => env('DB_USE_UTF8MB4', true) ? 'utf8mb4_unicode_ci' : 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'prefix' => env('DB_PREFIX', ''),
            'charset' => 'utf8',
            'schema' => 'public',
        ],

        'pgsqltesting' => [
            'driver' => 'pgsql',
            'host' => env('DB_TEST_HOST'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_TEST_DATABASE'),
            'username' => env('DB_TEST_USERNAME'),
            'password' => env('DB_TEST_PASSWORD'),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer set of commands than a typical key-value systems
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'cluster' => false,

        'default' => [
            'host' => env('REDIS_HOST', 'localhost'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_DATABASE', 0),
        ],

    ],

];

/*
 * If the instance is hosted on Heroku, then the database information
 * needs to be parsed from the environment variable provided by Heroku.
 * This is done below, added to the $db variable and then returned.
 */
if (env('HEROKU')) {
    $url = parse_url(env('CLEARDB_DATABASE_URL'));

    $db['connections']['heroku'] = [
        'driver' => 'mysql',
        'host' => $url['host'],
        'database' => starts_with($url['path'], '/') ? str_after($url['path'], '/') : $url['path'],
        'username' => $url['user'],
        'password' => $url['pass'],
        'charset' => env('DB_USE_UTF8MB4', true) ? 'utf8mb4' : 'utf8',
        'collation' => env('DB_USE_UTF8MB4', true) ? 'utf8mb4_unicode_ci' : 'utf8_unicode_ci',
        'prefix' => env('DB_PREFIX', ''),
        'strict' => false,
        'schema' => 'public',
    ];
    if (array_key_exists('port', $url)) {
        $db['connections']['heroku']['port'] = $url['port'];
    }
}

return $db;
