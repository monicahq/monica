<?php

declare(strict_types=1);

return [
    'date' => '2026-11-19',
    'entries' => [
        [
            'id' => 'entity-read-and-update',
            'kind' => 'Added',
            'group' => 'Kinds of record',
            'title' => 'Read and correct one record',
            'summary' => 'Two new endpoints read one record of a kind, such as an animal, and correct it. Every entity object now carries its values in fields, and links to itself and to its page in the application. An update keeps what it leaves out and empties a field sent as null.',
            'why' => 'A record could be listed and written down, but its values could not be read back nor corrected.',
            'breaking' => false,
            'changes' => [
                ['field' => 'GET /vaults/{vault}/entities/{entity}', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/entities/{entity}']],
                ['field' => 'PUT /vaults/{vault}/entities/{entity}', 'change' => 'Added', 'endpoints' => ['PUT /vaults/{vault}/entities/{entity}']],
                ['field' => 'fields', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/entity-types/{entityType}/entities', 'POST /vaults/{vault}/entity-types/{entityType}/entities', 'GET /vaults/{vault}/entities/{entity}', 'PUT /vaults/{vault}/entities/{entity}']],
                ['field' => 'links', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/entity-types/{entityType}/entities', 'POST /vaults/{vault}/entity-types/{entityType}/entities', 'GET /vaults/{vault}/entities/{entity}', 'PUT /vaults/{vault}/entities/{entity}']],
            ],
        ],
        [
            'id' => 'entity-create-on-a-chosen-template',
            'kind' => 'Added',
            'group' => 'Kinds of record',
            'title' => 'Write a record on the template you pick',
            'summary' => 'Creating a record takes an optional structure_version_id, the published version of another active template of the same kind, as creating a contact already does. Left out, the record starts from the template the vault uses.',
            'why' => 'Every account now holds a second animal template, Animal, beside Basic animal, and a record could only be written on the vault’s template.',
            'breaking' => false,
            'changes' => [
                ['field' => 'structure_version_id', 'change' => 'Added', 'endpoints' => ['POST /vaults/{vault}/entity-types/{entityType}/entities']],
            ],
        ],
        [
            'id' => 'contact-entities',
            'kind' => 'Added',
            'group' => 'Kinds of record',
            'title' => 'The records naming a contact',
            'summary' => 'A new endpoint lists the records of any kind, such as an animal, that name one contact in one of their relation fields, a page at a time.',
            'why' => 'A record could name a contact, but nothing went the other way, from the contact to the records naming them.',
            'breaking' => false,
            'changes' => [
                ['field' => 'GET /vaults/{vault}/contacts/{contact}/entities', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/contacts/{contact}/entities']],
            ],
        ],
        [
            'id' => 'possession-timeline',
            'kind' => 'Added',
            'group' => 'Possessions',
            'title' => 'The timeline of a possession',
            'summary' => 'A new endpoint lists everything that happened to a possession, read from its sections: transactions, insurance records, valuations, provenance events, maintenance records, loans and stays, the newest first. A section parameter narrows it to one.',
            'why' => 'The story of a possession was spread over seven endpoints, each in its own order.',
            'breaking' => false,
            'changes' => [
                ['field' => 'GET /vaults/{vault}/possessions/{possession}/timeline', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/possessions/{possession}/timeline']],
            ],
        ],
    ],
];
