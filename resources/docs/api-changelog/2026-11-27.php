<?php

declare(strict_types=1);

return [
    'date' => '2026-11-27',
    'entries' => [
        [
            'id' => 'ios-app-sign-in',
            'kind' => 'Added',
            'group' => 'Health',
            'title' => 'The Monica iOS app signs in through the mobile OAuth client',
            'summary' => 'The public OAuth client of the mobile apps now accepts the return address com.monicahq.ios:/oauth/callback next to the Android one. GET /status adds mobile.minimum_ios_version, the oldest version of the iOS app the instance works with, or null when it accepts every version.',
            'why' => 'The iOS app signs people in on any instance, official or self hosted, the way the Android app does.',
            'breaking' => false,
            'changes' => [
                ['field' => 'mobile.minimum_ios_version', 'change' => 'Added', 'endpoints' => ['GET /status']],
            ],
        ],
    ],
];
