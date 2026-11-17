<?php

declare(strict_types=1);

$vaultSlug = 'dunder-mifflin';

return [
    'name' => 'Search',
    'description' => 'Find the people, entities and life events that hold the words you type, anywhere in what is written on them.',
    'sections' => [
        [
            'id' => 'search-index',
            'title' => 'Search a vault',
            'label' => 'Search a vault',
            'method' => 'GET',
            'path' => '/vaults/{vault}/search',
            'examplePath' => '/vaults/'.$vaultSlug.'/search?query=farms',
            'description' => 'Search the records of one of your vaults, or of all of them, for every word you type.',
            'body' => [
                'A record is found when it holds every word, in any order, in its name, in any value written on it, or in the name of somebody linked to it: who took part in a life event, or who a contact has a relationship with. Case and accents are ignored, and a typing mistake is tolerated.',
                'Results come in groups, the way the product lists them: people, each entity type, then life events. Within a group, a record whose name holds the words comes before one where they are only in its values, and that one before a record found only through somebody linked to it.',
                'A value of a field marked sensitive is searched only with a key holding the sensitive-fields scope. Any other key never finds a record through such a value.',
                'Search needs the search engine of the instance. While it cannot be reached, the endpoint answers 503 and nothing else is affected.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => [
                ['name' => 'vault', 'type' => 'string', 'required' => true, 'description' => 'The vault to search, named either by the readable name it reports as slug or by its id. Both answer.'],
            ],
            'queryParams' => [
                ['name' => 'query', 'type' => 'string', 'required' => true, 'description' => 'The words to find, at least two characters and at most 255.', 'example' => 'farms'],
                ['name' => 'scope', 'type' => 'string', 'required' => false, 'default' => 'vault', 'description' => 'vault to search the vault named in the path, account to search every vault of the account.'],
                ['name' => 'group', 'type' => 'string', 'required' => false, 'description' => 'One group only, by the key a previous answer gave it, to page through it.'],
                ['name' => 'page', 'type' => 'integer', 'required' => false, 'default' => '1', 'description' => 'The page of the group asked for.'],
                ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'default' => '25', 'description' => 'How many results per group, at most 100.'],
            ],
            'returnFields' => [
                ['name' => 'data', 'type' => 'array', 'description' => 'The groups holding at least one result, in the order the product shows them.'],
                ['name' => 'data[]', 'type' => 'object', 'description' => 'One group.'],
                ['name' => 'data[].group', 'type' => 'string', 'description' => 'The key of the group: people, life-events, or entity-type- followed by the entity type.'],
                ['name' => 'data[].label', 'type' => 'string', 'description' => 'The name of the group, in the language of the account.'],
                ['name' => 'data[].total', 'type' => 'integer', 'description' => 'How many records the group holds in all.'],
                ['name' => 'data[].next_page', 'type' => 'integer', 'description' => 'The page to ask for next, with group, or null on the last page.'],
                ['name' => 'data[].results', 'type' => 'array', 'description' => 'The records of the group on this page, best match first.'],
                ['name' => 'data[].results[]', 'type' => 'object', 'description' => 'One record.'],
                ['name' => 'data[].results[].type', 'type' => 'string', 'description' => 'contact, entity or life_event.'],
                ['name' => 'data[].results[].id', 'type' => 'uuid', 'description' => 'The id the record answers to on its own endpoints.'],
                ['name' => 'data[].results[].name', 'type' => 'string', 'description' => 'The name the record is listed under.'],
                ['name' => 'data[].results[].vault', 'type' => 'object', 'description' => 'The vault the record lives in.'],
                ['name' => 'data[].results[].vault.id', 'type' => 'uuid', 'description' => 'The id of the vault.'],
                ['name' => 'data[].results[].vault.name', 'type' => 'string', 'description' => 'The name of the vault.'],
                ['name' => 'data[].results[].archived', 'type' => 'boolean', 'description' => 'Whether the record is archived.'],
                ['name' => 'data[].results[].date', 'type' => 'string', 'description' => 'For a life event, the day it happened, else null.'],
                ['name' => 'data[].results[].matched', 'type' => 'object', 'description' => 'Where the words were found: the label of the value and its text, or null when the name holds them. The text of a sensitive value is always null.'],
                ['name' => 'data[].results[].matched.label', 'type' => 'string', 'description' => 'The label of the value, or of the link, the words were found in.'],
                ['name' => 'data[].results[].matched.excerpt', 'type' => 'string', 'description' => 'The text of that value, or null for a sensitive value or a link.'],
                ['name' => 'meta', 'type' => 'object', 'description' => 'What was asked.'],
                ['name' => 'meta.query', 'type' => 'string', 'description' => 'The words searched.'],
                ['name' => 'meta.scope', 'type' => 'string', 'description' => 'vault or account.'],
            ],
            'returns' => 'The groups of records found. A vault belonging to somebody else answers 404, a query under two characters 422, and an unreachable search engine 503.',
            'response' => [
                'data' => [
                    [
                        'group' => 'people',
                        'label' => 'People',
                        'total' => 1,
                        'next_page' => null,
                        'results' => [
                            [
                                'type' => 'contact',
                                'id' => '9f1c3a2e-7b4d-4c1a-8e2f-5d6b7a8c9e01',
                                'name' => 'Dwight Schrute',
                                'vault' => ['id' => '2c7e9b14-1f3a-4d5e-9a8b-6c4d3e2f1a07', 'name' => 'Dunder Mifflin'],
                                'archived' => false,
                                'date' => null,
                                'matched' => ['label' => 'Employer', 'excerpt' => 'Schrute Farms'],
                            ],
                        ],
                    ],
                ],
                'meta' => ['query' => 'farms', 'scope' => 'vault'],
            ],
        ],
    ],
];
