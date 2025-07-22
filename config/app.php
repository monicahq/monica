<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_REALNAME', env('APP_NAME', 'Monica')),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL'),

    'force_url' => (bool) env('APP_FORCE_URL', false),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Protocols
    |--------------------------------------------------------------------------
    |
    | This array contains the protocols used for social media links. The keys
    | are the names of the social media platforms, and the values are the
    | base URLs for those platforms. You can add or remove protocols as needed.
    |
    | */

    'social_protocols' => [
        'Facebook' => [
            'name_translation_key' => trans_key('Facebook'),
            'url' => 'https://www.facebook.com/',
            'type' => 'X-SOCIAL-PROFILE',
        ],
        'Whatsapp' => [
            'name_translation_key' => trans_key('Whatsapp'),
            'url' => 'https://wa.me/',
            'type' => 'X-SOCIAL-PROFILE',
        ],
        'Telegram' => [
            'name_translation_key' => trans_key('Telegram'),
            'url' => 'https://t.me/',
            'type' => 'IMPP',
        ],
        'LinkedIn' => [
            'name_translation_key' => trans_key('LinkedIn'),
            'url' => 'https://www.linkedin.com/in/',
            'type' => 'X-SOCIAL-PROFILE',
        ],
        'Instagram' => [
            'name_translation_key' => trans_key('Instagram'),
            'url' => 'https://www.instagram.com/',
            'type' => 'X-SOCIAL-PROFILE',
        ],
        'Twitter' => [
            'name_translation_key' => trans_key('Twitter'),
            'url' => 'https://twitter.com/',
            'type' => 'IMPP',
        ],
        'Hangouts' => [
            'name_translation_key' => trans_key('Hangouts'),
            'url' => 'https://hangouts.google.com/chat/person/',
            'type' => 'IMPP',
        ],
        'Mastodon' => [
            'name_translation_key' => trans_key('Mastodon'),
            'url' => '',
            'type' => 'X-SOCIAL-PROFILE',
        ],
        'Bluesky' => [
            'name_translation_key' => trans_key('Bluesky'),
            'url' => 'https://bsky.app/profile/',
            'type' => 'X-SOCIAL-PROFILE',
        ],
        'Threads' => [
            'name_translation_key' => trans_key('Threads'),
            'url' => 'https://www.threads.net/@',
            'type' => 'X-SOCIAL-PROFILE',
        ],
        'GitHub' => [
            'name_translation_key' => trans_key('GitHub'),
            'url' => 'https://github.com/',
            'type' => 'X-SOCIAL-PROFILE',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Contact Information Groups
    |--------------------------------------------------------------------------
    |
    | This array defines the groups of contact information types. Each group
    | has a name translation key that is used for display purposes.
    |
     */

    'contact_information_groups' => [
        'email' => [
            'name_translation_key' => trans_key('Email address'),
        ],
        'phone' => [
            'name_translation_key' => trans_key('Phone number'),
        ],
        'IMPP' => [
            'name_translation_key' => trans_key('Instant messaging'),
        ],
        'X-SOCIAL-PROFILE' => [
            'name_translation_key' => trans_key('Social profile'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Contact Information Kinds
    |--------------------------------------------------------------------------
    |
    | This array defines the base kinds of contact information. This is used
    | for email and phone numbers. You can add more kinds as needed.
    |
     */

    'contact_information_kinds' => [
        'email' => [
            [
                'id' => 'work',
                'name_translation_key' => trans_key('🏢 Work'),
            ],
            [
                'id' => 'home',
                'name_translation_key' => trans_key('🏡 Home'),
            ],
            [
                'id' => 'personal',
                'name_translation_key' => trans_key('🧑🏼 Personal'),
            ],
            [
                'id' => 'other',
                'name_translation_key' => trans_key('❔ Other'),
            ],
        ],
        'phone' => [
            [
                'id' => 'work',
                'name_translation_key' => trans_key('🏢 Work'),
            ],
            [
                'id' => 'home',
                'name_translation_key' => trans_key('🏡 Home'),
            ],
            [
                'id' => 'cell',
                'name_translation_key' => trans_key('📱 Mobile'),
            ],
            [
                'id' => 'fax',
                'name_translation_key' => trans_key('📠 Fax'),
            ],
            [
                'id' => 'pager',
                'name_translation_key' => trans_key('📟 Pager'),
            ],
            [
                'id' => 'other',
                'name_translation_key' => trans_key('❔ Other'),
            ],
        ],
    ],
];
