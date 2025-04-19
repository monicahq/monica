<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'telegram-bot-api' => [
        'token' => env('TELEGRAM_BOT_TOKEN', null),
        'bot_url' => env('TELEGRAM_BOT_URL'),
        'webhook' => env('TELEGRAM_BOT_WEBHOOK_URL'),
    ],

    'uploadcare' => [
        'public_key' => env('UPLOADCARE_PUBLIC_KEY', null),
        'private_key' => env('UPLOADCARE_PRIVATE_KEY', null),
    ],

    /*
     * Socialite providers
     */

    'azure' => [
        'client_id' => env('AZURE_CLIENT_ID'),
        'client_secret' => env('AZURE_CLIENT_SECRET'),
        'redirect' => env('AZURE_REDIRECT_URI', '/auth/azure/callback'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI', '/auth/facebook/callback'),
        'logo' => '/img/auth/facebook.png',
    ],

    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_REDIRECT_URI', '/auth/github/callback'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

    'linkedin' => [
        'client_id' => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'redirect' => env('LINKEDIN_REDIRECT_URI', '/auth/linkedin/callback'),
    ],

    'saml2' => [
        'name' => env('SAML2_NAME'),
        'metadata' => env('SAML2_METADATA'),
        'acs' => env('SAML2_ACS'),
        'entityid' => env('SAML2_ENTITY_ID'),
        'certificate' => env('SAML2_CERTIFICATE'),
        'sp_acs' => env('SAML2_REDIRECT_URI', '/auth/saml2/callback'),
        'logo' => env('SAML2_LOGO', '/img/auth/saml2.svg'),
    ],
    'kanidm' => [
        'client_id' => env('KANIDM_CLIENT_ID'),
        'client_secret' => env('KANIDM_CLIENT_SECRET'),
        'redirect' => env('KANIDM_REDIRECT_URI'),
        'base_url' => env('KANIDM_BASE_URL'),
    ],
    'keycloak' => [
        'client_id' => env('KEYCLOAK_CLIENT_ID'),
        'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),
        'redirect' => env('KEYCLOAK_REDIRECT_URI', '/auth/keycloak/callback'),
        'base_url' => env('KEYCLOAK_BASE_URL'),
        'realms' => env('KEYCLOAK_REALM'),
    ],

];
