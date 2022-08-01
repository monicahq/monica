<?php

return [

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
    | URL of the documentation center
    |--------------------------------------------------------------------------
    |
    | This platform hosts the help documentation.
    |
    */

    'help_center_url' => 'https://docs-ivory-one.vercel.app/docs/',

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
        'last_updated_contacts' => 'vaults/dashboard#last-updated-contacts',
        'settings_preferences_help' => 'account-settings/manage-preferences#help-display',
        'settings_preferences_language' => 'account-settings/manage-preferences#language',
        'settings_preferences_contact_names' => 'account-settings/manage-preferences#customize-contact-names',
        'settings_preferences_date' => 'account-settings/manage-preferences#date-format',
        'settings_preferences_numerical_format' => 'account-settings/manage-preferences#numerical-format',
        'settings_preferences_timezone' => 'account-settings/manage-preferences#timezone',
        'settings_preferences_maps' => 'account-settings/manage-preferences#maps-preferences',
    ],
];
