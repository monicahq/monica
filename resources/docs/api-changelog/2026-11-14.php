<?php

declare(strict_types=1);

return [
    'date' => '2026-11-14',
    'entries' => [
        [
            'id' => 'me-update-email-ignored',
            'kind' => 'Changed',
            'group' => 'Your profile',
            'title' => 'Updating your profile no longer changes your address',
            'summary' => 'An `email` sent to update your profile is ignored. Change the address you sign in with from the security settings, which ask for your password first.',
            'why' => 'Any key could change the address its owner signs in with, and with it the way to reset their password, without the password the interface asks for.',
            'breaking' => true,
            'impact' => 'An integration that changed the address through this endpoint keeps the old one, and the response shows it.',
            'changes' => [
                ['field' => 'email', 'change' => 'Removed', 'endpoints' => ['PUT /me']],
            ],
        ],
        [
            'id' => 'api-keys-scopes-held',
            'kind' => 'Changed',
            'group' => 'API keys',
            'title' => 'A key only gives the scopes it holds',
            'summary' => 'Creating a key through the API refuses any scope the key making the request does not hold itself. Asking for sensitive fields from a key without them answers 422 on sensitive_fields.',
            'why' => 'Any key could mint a second key reaching the sensitive fields its own owner had kept it away from.',
            'breaking' => true,
            'impact' => 'An integration that asks for sensitive fields with a key that lacks them gets 422. Create that key from the security settings instead.',
            'changes' => [
                ['field' => 'sensitive_fields', 'change' => 'Changed', 'endpoints' => ['POST /api-keys']],
            ],
        ],
    ],
];
