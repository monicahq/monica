<?php

declare(strict_types=1);
use Illuminate\Notifications\Messages\VonageMessage;
use NotificationChannels\Telegram\TelegramMessage;

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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_REDIRECT_URI', '/sso/github/callback'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/sso/google/callback'),
    ],

    'microsoft' => [
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        'redirect' => env('MICROSOFT_REDIRECT_URI', '/sso/microsoft/callback'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI', '/sso/facebook/callback'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    | The two channels that reach an outside service. Their packages are
    | suggested rather than required, so `installed` says whether this
    | installation has one. It is read from configuration rather than asked of
    | the autoloader where it is used, so that a test can pretend a package is
    | absent. Cached configuration freezes it: run `config:cache` again after
    | installing or removing one.
    */

    'telegram' => [
        'installed' => class_exists(TelegramMessage::class),
        'token' => env('TELEGRAM_BOT_TOKEN'),
        'bot_url' => env('TELEGRAM_BOT_URL'), // Should follow: https://t.me/<your_bot_username>
        'webhook_secret' => env('TELEGRAM_BOT_WEBHOOK_SECRET'), // Generate a secret with: openssl rand -hex 32
    ],

    'vonage' => [
        'installed' => class_exists(VonageMessage::class),
    ],

];
