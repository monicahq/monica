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
        [
            'id' => 'oauth-connections',
            'kind' => 'Changed',
            'group' => 'API keys',
            'title' => 'Every app connected through OAuth is listed as a connection',
            'summary' => 'The three mobile-sessions endpoints are replaced by oauth-connections, which list, show and end every app connected through OAuth: the mobile app and assistants alike. A connection carries client_name and scopes next to label, which replaces device_name. Every client now sees the consent page at every sign-in, so prompt=none is no longer honoured or advertised. POST /oauth/revoke ends the connection of any client. A scope a client may not ask for is refused by sending invalid_scope back to the return address, when the client registered it, and with a 400 otherwise.',
            'why' => 'There is one OAuth sign-in for every client, so there is one list of what is connected.',
            'breaking' => true,
            'impact' => 'Call /oauth-connections where you called /mobile-sessions, read label where you read device_name, and expect type oauth_connection.',
            'changes' => [
                ['field' => 'oauth_connection', 'change' => 'Added', 'endpoints' => ['GET /oauth-connections', 'GET /oauth-connections/{oauthConnection}', 'DELETE /oauth-connections/{oauthConnection}']],
                ['field' => 'mobile_session', 'change' => 'Removed', 'endpoints' => ['GET /mobile-sessions', 'GET /mobile-sessions/{mobileSession}', 'DELETE /mobile-sessions/{mobileSession}']],
            ],
        ],
    ],
];
