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
        [
            'id' => 'life-event-details',
            'kind' => 'Added',
            'group' => 'Life events',
            'title' => 'Read everything recorded about a life event',
            'summary' => 'A life event now carries date_precision, ended_on, happened_at_time and timezone, the role and notes of each contact who was there, and the entities that were there in a new entity_participants list. participants still holds contacts only.',
            'why' => 'The vault timeline in the application now shows every detail of a life event, and everything the application shows can be read through the API.',
            'breaking' => false,
            'changes' => [
                ['field' => 'date_precision', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/life-events', 'GET /vaults/{vault}/life-events/{lifeEvent}', 'POST /vaults/{vault}/life-events', 'PUT /vaults/{vault}/life-events/{lifeEvent}', 'GET /vaults/{vault}/groups/{group}/life-events']],
                ['field' => 'ended_on', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/life-events', 'GET /vaults/{vault}/life-events/{lifeEvent}', 'POST /vaults/{vault}/life-events', 'PUT /vaults/{vault}/life-events/{lifeEvent}', 'GET /vaults/{vault}/groups/{group}/life-events']],
                ['field' => 'happened_at_time', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/life-events', 'GET /vaults/{vault}/life-events/{lifeEvent}', 'POST /vaults/{vault}/life-events', 'PUT /vaults/{vault}/life-events/{lifeEvent}', 'GET /vaults/{vault}/groups/{group}/life-events']],
                ['field' => 'timezone', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/life-events', 'GET /vaults/{vault}/life-events/{lifeEvent}', 'POST /vaults/{vault}/life-events', 'PUT /vaults/{vault}/life-events/{lifeEvent}', 'GET /vaults/{vault}/groups/{group}/life-events']],
                ['field' => 'participants[].role', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/life-events', 'GET /vaults/{vault}/life-events/{lifeEvent}', 'POST /vaults/{vault}/life-events', 'PUT /vaults/{vault}/life-events/{lifeEvent}', 'GET /vaults/{vault}/groups/{group}/life-events']],
                ['field' => 'participants[].notes', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/life-events', 'GET /vaults/{vault}/life-events/{lifeEvent}', 'POST /vaults/{vault}/life-events', 'PUT /vaults/{vault}/life-events/{lifeEvent}', 'GET /vaults/{vault}/groups/{group}/life-events']],
                ['field' => 'entity_participants', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/life-events', 'GET /vaults/{vault}/life-events/{lifeEvent}', 'POST /vaults/{vault}/life-events', 'PUT /vaults/{vault}/life-events/{lifeEvent}', 'GET /vaults/{vault}/groups/{group}/life-events']],
            ],
        ],
    ],
];
