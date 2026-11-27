<?php

declare(strict_types=1);

return [
    'date' => '2026-11-27',
    'entries' => [
        [
            'id' => 'vault-card-values',
            'kind' => 'Added',
            'group' => 'Vaults',
            'title' => 'A vault says when it was last opened, who is in it and how many dates are due',
            'summary' => 'Every vault now carries last_visited_at, faces (up to four contacts, each with an id, a name and an avatar_url) and upcoming_reminder_count, on the list, on a single vault and on what creating or updating one returns. Listing vaults also takes sort=recent, which puts the vault opened most recently first.',
            'why' => 'A client can draw the same vault cards as the vaults page of the application without working any of it out itself.',
            'breaking' => false,
            'changes' => [
                ['field' => 'last_visited_at', 'change' => 'Added', 'endpoints' => ['GET /vaults', 'GET /vaults/{vault}', 'POST /vaults', 'PUT /vaults/{vault}']],
                ['field' => 'faces', 'change' => 'Added', 'endpoints' => ['GET /vaults', 'GET /vaults/{vault}', 'POST /vaults', 'PUT /vaults/{vault}']],
                ['field' => 'upcoming_reminder_count', 'change' => 'Added', 'endpoints' => ['GET /vaults', 'GET /vaults/{vault}', 'POST /vaults', 'PUT /vaults/{vault}']],
                ['field' => 'sort', 'change' => 'Added', 'endpoints' => ['GET /vaults']],
            ],
        ],
        [
            'id' => 'contact-avatar-show',
            'kind' => 'Added',
            'group' => 'Contacts',
            'title' => 'The picture of a contact can be fetched with a key',
            'summary' => 'GET /vaults/{vault}/contacts/{contact}/avatar returns the small picture of a contact, 96 pixels square, to a key. The faces of a vault point at it.',
            'why' => 'Until now the picture was only served to somebody signed in to the application, so a client holding a key could only draw initials.',
            'breaking' => false,
            'changes' => [
                ['field' => 'GET /vaults/{vault}/contacts/{contact}/avatar', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/contacts/{contact}/avatar']],
            ],
        ],
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
