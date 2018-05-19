<?php

return [

   /*
    |--------------------------------------------------------------------------
    | Disable User registration
    |--------------------------------------------------------------------------
    |
    | Disables registration of new users
    |
    */
    'disable_signup' => env('APP_DISABLE_SIGNUP', false),

    /*
    |--------------------------------------------------------------------------
    | New User Email Notification
    |--------------------------------------------------------------------------
    |
    | Email to notify when new user registers.
    |
    */
    'email_new_user_notification' => env('APP_EMAIL_NEW_USERS_NOTIFICATION'),

    /*
    |--------------------------------------------------------------------------
    | User and error tracking
    |--------------------------------------------------------------------------
    |
    | We provide placeholders for Sentry.
    |
    */
    'sentry_support' => env('SENTRY_SUPPORT', false),

    /*
    |--------------------------------------------------------------------------
    | Access to paid features
    |--------------------------------------------------------------------------
    |
    | This value determines if the instance can access the paid features that
    | are available on https://monicahq.com, for free.
    | If set to false, the instance won't have access to the paid features.
    |
    | Available Settings: true, false
    |
    */
    'requires_subscription' => env('REQUIRES_SUBSCRIPTION', false),

    /*
    |--------------------------------------------------------------------------
    | Paid plan settings
    |--------------------------------------------------------------------------
    |
    | This value determines the name and the cost of the paid plan offered
    | on https://monicahq.com. These settings make sense only if you do activate
    | the `unlock_paid_features` above.
    |
    |
    */
   'paid_plan_monthly_friendly_name' => env('PAID_PLAN_MONTHLY_FRIENDLY_NAME', null),
   'paid_plan_monthly_id' => env('PAID_PLAN_MONTHLY_ID', null),
   'paid_plan_monthly_price' => env('PAID_PLAN_MONTHLY_PRICE', null),
   'paid_plan_annual_friendly_name' => env('PAID_PLAN_ANNUAL_FRIENDLY_NAME', null),
   'paid_plan_annual_id' => env('PAID_PLAN_ANNUAL_ID', null),
   'paid_plan_annual_price' => env('PAID_PLAN_ANNUAL_PRICE', null),

    /*
    |--------------------------------------------------------------------------
    | Ping that checks if a new version is available
    |--------------------------------------------------------------------------
    |
    | This is used to indicate if you allow the ping to be sent to
    | version.monicahq.com to check if a new version is available.
    |
    */
    'check_version' => env('CHECK_VERSION', true),

    /*
    |--------------------------------------------------------------------------
    | Allow access through the API of the public statistics
    |--------------------------------------------------------------------------
    |
    | Your Monica instance has some statistics (number of users, number of
    | contacts,...). Those data can be public (they are on MonicaHQ.com).
    | This setting lets you access those data through a public API call.
    |
    */
    'allow_statistics_through_public_api_access' => env('ALLOW_STATISTICS_THROUGH_PUBLIC_API_ACCESS', false),
    /*
    |--------------------------------------------------------------------------
    | URL of the server for the version check
    |--------------------------------------------------------------------------
    |
    | This is the server that is used to ping if a new version is avaialble.
    | Do not change this manually.
    |
    */
    'weekly_ping_server_url' => 'https://version.monicahq.com/ping',

    /*
    |--------------------------------------------------------------------------
    | List of default relationship type group
    |--------------------------------------------------------------------------
    |
    | This is used to populate the relationship type groups table.
    |
    */
    'default_relationship_type_group' => [
        'love',
        'family',
        'friend',
        'work',
    ],

    /*
    |--------------------------------------------------------------------------
    | Specific to the official Monica mobile application
    |--------------------------------------------------------------------------
    |
    | We need to pass a specific client ID and client secret that only the
    | official mobile application can access - in order to protect the privacy
    | of the instance (which has a lot of data).
    | You can check what we do with this data on the mobile application on the
    | official repository: https://github.com/monicahq/chandler.
    |
    */
    'mobile_client_id' => env('MOBILE_CLIENT_ID', null),
    'mobile_client_secret' => env('MOBILE_CLIENT_SECRET', null),

    /*
    |--------------------------------------------------------------------------
    | Version of the application that you run
    |--------------------------------------------------------------------------
    |
    | This is used to indicate which version of Monica you are running. You
    | should not change this setting yourself. DO NOT CHANGE IT YOURSELF. Or
    | bad things will happen.
    |
    */
    'app_version' => '2.1.1',
];
