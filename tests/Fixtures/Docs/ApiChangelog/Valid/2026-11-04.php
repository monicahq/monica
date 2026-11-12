<?php

declare(strict_types=1);

return [
    'date' => '2026-11-04',
    'note' => 'One breaking change, in a field read every day.',
    'entries' => [
        [
            'id' => 'contact-birthdate-format',
            'kind' => 'Changed',
            'group' => 'Contacts',
            'title' => 'Birthdates are a calendar date, not a timestamp',
            'summary' => 'The birthdate of Michael Scott is now returned as 1964-03-15, with no time and no time zone.',
            'why' => 'A birthday has no time of day, and midnight UTC moved it to the day before west of Greenwich.',
            'breaking' => true,
            'impact' => 'Code parsing birthdate as a full timestamp fails on a value with no time part.',
            'changes' => [
                ['field' => 'birthdate', 'change' => 'Changed', 'endpoints' => ['GET /vaults/{vault}/contacts', 'GET /vaults/{vault}/contacts/{contact}']],
            ],
            'example' => [
                'label' => 'GET contacts/{contact}',
                'before' => '{ "birthdate": "1964-03-15T00:00:00Z" }',
                'after' => '{ "birthdate": "1964-03-15" }',
            ],
        ],
        [
            'id' => 'contact-list-filter',
            'kind' => 'Added',
            'group' => 'Contacts',
            'title' => 'Adds filtering the contact list by nickname',
            'summary' => 'List contacts accepts a nickname, so Dwight can find Big Tuna without paging.',
            'why' => 'Fetching every contact to keep one cost a page of requests per hundred people.',
            'changes' => [
                ['field' => 'filter[nickname]', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/contacts']],
            ],
        ],
        [
            'id' => 'profile-locale-removed',
            'kind' => 'Removed',
            'group' => 'Your profile',
            'title' => 'Removes the regional manager title from your profile',
            'summary' => 'Update your profile no longer accepts a job title.',
            'why' => 'Nobody but Michael ever set it.',
            'breaking' => true,
            'impact' => 'A request that still sends job_title answers 422.',
            'changes' => [
                ['field' => 'job_title', 'change' => 'Removed', 'endpoints' => ['PUT /me/job-title']],
            ],
        ],
        [
            'id' => 'group-motto',
            'kind' => 'Added',
            'group' => 'Groups',
            'title' => 'Adds a motto to the group object',
            'summary' => 'The Party planning committee can now carry a motto.',
            'why' => 'Angela asked for one.',
            'changes' => [
                ['field' => 'motto', 'change' => 'Added', 'endpoints' => []],
            ],
        ],
    ],
];
