<?php

declare(strict_types=1);

use App\Models\OAuthConnection;

return [
    'name' => 'Health',
    'description' => 'Whether the instance is answering, and whether what it depends on is answering it.',
    'sections' => [
        [
            'id' => 'status-show',
            'returnFields' => [
                ['name' => 'status', 'type' => 'string', 'description' => 'Always ok in this answer. An instance that cannot reach its database answers 503 instead, carrying status of error and nothing else, and one that is not running answers nothing at all. Those three are what a monitor watches for.'],
                ['name' => 'version', 'type' => 'string', 'description' => 'The release the instance runs, as a semantic version such as 3.0.0. It is how a caller can tell whether a capability it needs has landed here yet.'],
                ['name' => 'instance', 'type' => 'string', 'description' => 'The host answering, so a monitor watching several self hosted instances can tell them apart.'],
                ['name' => 'mobile', 'type' => 'object', 'description' => 'How the Monica mobile apps sign in to this instance.'],
                ['name' => 'mobile.api_version', 'type' => 'integer', 'description' => 'The version of this API the apps use. An app that needs a higher one asks for Monica to be updated.'],
                ['name' => 'mobile.client_id', 'type' => 'string', 'description' => 'The identifier of the public OAuth client of the apps. Every instance holds it, created when Monica was installed or updated.'],
                ['name' => 'mobile.scope', 'type' => 'string', 'description' => 'The scope the apps ask for: api, which reaches this API like a key without extra scopes.'],
                ['name' => 'mobile.registration_enabled', 'type' => 'boolean', 'description' => 'Whether the instance accepts new accounts, so the app can say so before opening the registration page.'],
                ['name' => 'mobile.minimum_android_version', 'type' => 'string', 'description' => 'The oldest version of the Android app this instance still works with, such as 1.2.0, or null when it accepts every version.'],
                ['name' => 'mobile.minimum_ios_version', 'type' => 'string', 'description' => 'The oldest version of the iOS app this instance still works with, such as 1.2.0, or null when it accepts every version.'],
            ],
            'title' => 'Check status',
            'label' => 'Check status',
            'method' => 'GET',
            'path' => '/status',
            'auth' => false,
            'description' => 'Tells you whether the instance is up and which release it runs. No token needed, so you can point a monitor at it.',
            'rateLimit' => 'Sixty requests per minute, per address.',
            'returns' => 'The state of the instance, or a 503 status when something it depends on is unreachable.',
            'response' => [
                'status' => 'ok',
                'version' => '3.0.0',
                'instance' => 'app.monicahq.com',
                'mobile' => [
                    'api_version' => 1,
                    'client_id' => OAuthConnection::MOBILE_CLIENT_ID,
                    'scope' => 'api',
                    'registration_enabled' => true,
                    'minimum_android_version' => null,
                    'minimum_ios_version' => null,
                ],
            ],
        ],
    ],
];
