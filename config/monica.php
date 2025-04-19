<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Version of the application
    |--------------------------------------------------------------------------
    |
    | This value returns the current version of the application.
    |
    */

    'app_version' => env('APP_VERSION', readVersion(__DIR__.'/.version', 'git describe --abbrev=0 --tags', '0.0.0')),

    /*
    |--------------------------------------------------------------------------
    | Disable User registration
    |--------------------------------------------------------------------------
    |
    | Disables registration of new users
    |
    */
    'disable_signup' => (bool) env('APP_DISABLE_SIGNUP', false),

    /*
    |--------------------------------------------------------------------------
    | Commit hash of the application
    |--------------------------------------------------------------------------
    |
    | This value returns the current commit hash of the application sources.
    |
    */

    'commit' => env('APP_COMMIT', readVersion(__DIR__.'/.commit', 'git log --pretty="%H" -n1 HEAD')),

    /*
    |--------------------------------------------------------------------------
    | DEFAULT STORAGE LIMIT IN MB
    |--------------------------------------------------------------------------
    |
    | This value represents the default storage limit for every new account in
    | this instance. The value is represented in MB.
    |
    */

    'default_storage_limit_in_mb' => env('DEFAULT_STORAGE_LIMIT', 50),

    /*
    |--------------------------------------------------------------------------
    | Mapbox API key
    |--------------------------------------------------------------------------
    |
    | Used to display static maps. See https://docs.mapbox.com/help/how-mapbox-works/static-maps/
    |
    */

    'mapbox_api_key' => env('MAPBOX_API_KEY', null),

    /*
    |--------------------------------------------------------------------------
    | Mapbox username
    |--------------------------------------------------------------------------
    |
    | Used to display static maps. See https://docs.mapbox.com/help/how-mapbox-works/static-maps/
    | You need this if you want to display a custom style for your maps.
    | If you have defined a custom style, you need to add your username.
    |
    */

    'mapbox_username' => env('MAPBOX_USERNAME', 'mapbox'),

    /*
    |--------------------------------------------------------------------------
    | Mapbox custom style
    |--------------------------------------------------------------------------
    |
    | Used to render static maps. If you have defined a custom style for your
    | maps on Mapbox, you should indicate its name here, along with your
    | username (see above).
    |
    */

    'mapbox_custom_style_name' => env('MAPBOX_CUSTOM_STYLE_NAME', 'streets-v11'),

    /*
    |--------------------------------------------------------------------------
    | API key for geolocation service.
    |--------------------------------------------------------------------------
    |
    | We use LocationIQ (https://locationiq.com/) to translate addresses to
    | latitude/longitude coordinates. We could use Google instead but we don't
    | want to give anything to Google, ever.
    | LocationIQ offers 10,000 free requests per day.
    |
    */

    'location_iq_api_key' => env('LOCATION_IQ_API_KEY', null),

    /*
    |--------------------------------------------------------------------------
    | Locationiq API Url
    |--------------------------------------------------------------------------
    |
    | Url to call LocationHQ api. See https://locationiq.com/docs
    |
    */

    'location_iq_url' => env('LOCATION_IQ_URL', 'https://us1.locationiq.com/v1/'),

    /*
    |--------------------------------------------------------------------------
    | URL of the documentation center
    |--------------------------------------------------------------------------
    |
    | This platform hosts the help documentation.
    |
    */

    'help_center_url' => 'https://docs.monicahq.com/',

    /*
    |--------------------------------------------------------------------------
    | URL of the repository
    |--------------------------------------------------------------------------
    |
    | Links to the sources of the project.
    |
    */

    'repository' => 'https://github.com/monicahq/monica/',

    /*
    |--------------------------------------------------------------------------
    | Max notification failures
    |--------------------------------------------------------------------------
    |
    | When this number of failures is reached, we disable the notification channel.
    |
    */

    'max_notification_failures' => 10,

    /*
    |--------------------------------------------------------------------------
    | HELP CENTER URL
    |--------------------------------------------------------------------------
    |
    | These are the links that are used in the UI to point to the right help
    | section.
    |
    */

    'help_links' => [
        'vault_create' => 'vaults/introduction',

        'last_updated_contacts' => 'vaults/dashboard#last-updated-contacts',

        'settings_preferences_help' => 'user-and-account-settings/manage-preferences#help-display',
        'settings_preferences_language' => 'user-and-account-settings/manage-preferences#language',
        'settings_preferences_contact_names' => 'user-and-account-settings/manage-preferences#customize-contact-names',
        'settings_preferences_date' => 'user-and-account-settings/manage-preferences#date-format',
        'settings_preferences_numerical_format' => 'user-and-account-settings/manage-preferences#numerical-format',
        'settings_preferences_timezone' => 'user-and-account-settings/manage-preferences#timezone',
        'settings_preferences_maps' => 'user-and-account-settings/manage-preferences#timezone',
        'settings_account_deletion' => 'user-and-account-settings/account-deletion',
    ],
];
