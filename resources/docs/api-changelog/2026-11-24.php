<?php

declare(strict_types=1);

return [
    'date' => '2026-11-24',
    'entries' => [
        [
            'id' => 'vault-section-visibility',
            'kind' => 'Added',
            'group' => 'Vaults',
            'title' => 'Say which sections a vault shows',
            'summary' => 'A vault now carries shows_people, shows_things and shows_knowledge, true until somebody hides that section. PUT /vaults/{vault} accepts each of them, and keeps any you leave out. They change only what the application draws: every endpoint answers the same whatever they say.',
            'why' => 'A vault used for one purpose can hide the sections it never uses, and the setting can be changed from the application, the API or an assistant alike.',
            'breaking' => false,
            'changes' => [
                ['field' => 'shows_people', 'change' => 'Added', 'endpoints' => ['GET /vaults', 'GET /vaults/{vault}', 'POST /vaults', 'PUT /vaults/{vault}']],
                ['field' => 'shows_things', 'change' => 'Added', 'endpoints' => ['GET /vaults', 'GET /vaults/{vault}', 'POST /vaults', 'PUT /vaults/{vault}']],
                ['field' => 'shows_knowledge', 'change' => 'Added', 'endpoints' => ['GET /vaults', 'GET /vaults/{vault}', 'POST /vaults', 'PUT /vaults/{vault}']],
            ],
        ],
    ],
];
