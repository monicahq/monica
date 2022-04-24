<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. A "local" driver, as well as a variety of cloud
    | based drivers are available for your choosing. Just store away!
    |
    | Supported: "local", "ftp", "sftp", s3"
    |
    */

    'default' => env('FILESYSTEM_DISK', env('DEFAULT_FILESYSTEM', 'public')),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => true,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => true,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID', env('AWS_KEY')),
            'secret' => env('AWS_SECRET_ACCESS_KEY', env('AWS_SECRET')),
            'region' => env('AWS_DEFAULT_REGION', env('AWS_REGION')),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT', env('AWS_SERVER', '') ? 'https://'.env('AWS_SERVER') : null),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', env('S3_PATH_STYLE', false)),
            'throw' => true,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Filesystem visibility
    |--------------------------------------------------------------------------
    |
    | If this is set to private, all files are stored privately, and are
    | delivered using a proxy-url by monica, providing access of the files in
    | the storage.
    | This means only the authenticated user will be able to open the files.
    |
    | You might store the files publicly if you're on a private instance and if
    | you want to make files accessible from the outside - the url files are
    | still private and not easy to guess.
    |
    | Supported: "private", "public"
    |
    */

    'default_visibility' => env('FILESYSTEM_DEFAULT_VISIBILITY', 'private'),

    /*
    |--------------------------------------------------------------------------
    | Cache control for files
    |--------------------------------------------------------------------------
    |
    | Defines the Cache-Control header used to serve files.
    | Default: 'max-age=2628000' for 1 month cache.
    |
    */

    'default_cache_control' => env('DEFAULT_CACHE_CONTROL', 'private, max-age=2628000'),

];
