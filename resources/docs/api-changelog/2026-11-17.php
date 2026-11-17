<?php

declare(strict_types=1);

return [
    'date' => '2026-11-17',
    'entries' => [
        [
            'id' => 'changelog-entries',
            'kind' => 'Added',
            'group' => 'What’s new',
            'title' => 'Adds the changes of What’s new',
            'summary' => 'List the changes published over the last 30 or 90 days, with how many you have not seen yet, and mark them all as seen. Vote on a change, take the vote back, read its comments and write one, public, anonymous or for the administrators only.',
            'why' => 'An integration could not tell its people what changed in the product, nor carry their feedback back.',
            'changes' => [
                ['field' => 'changelog_entry', 'change' => 'Added', 'endpoints' => ['GET /changelog-entries', 'PUT /changelog-entries/seen']],
                ['field' => 'changelog_vote', 'change' => 'Added', 'endpoints' => ['PUT /changelog-entries/{entry}/vote', 'DELETE /changelog-entries/{entry}/vote']],
                ['field' => 'changelog_comment', 'change' => 'Added', 'endpoints' => ['GET /changelog-entries/{entry}/comments', 'POST /changelog-entries/{entry}/comments']],
            ],
            'example' => [
                'label' => 'POST changelog-entries/{entry}/comments',
                'after' => '{ "body": "Can we book it for the Dundies?", "is_anonymous": true }',
            ],
        ],
    ],
];
