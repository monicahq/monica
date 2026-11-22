<?php

declare(strict_types=1);

return [
    'date' => '2026-11-22',
    'entries' => [
        [
            'id' => 'countries-listed-and-picked-by-code',
            'kind' => 'Added',
            'group' => 'Templates',
            'title' => 'Every country, and an address naming one by its code',
            'summary' => 'GET /countries lists every country with its two letter code and its name in the language of the request, the favourites of the account first. The country of an address now also takes that two letter code, whether or not the country is a favourite. Sending the id of a favourite works as before, and country_name records a place that is not on the list, as written and without a code.',
            'why' => 'An address could only name a country among the favourites of the account, which had to be added in the settings first.',
            'breaking' => false,
            'changes' => [
                ['field' => 'countries', 'change' => 'Added', 'endpoints' => ['GET /countries']],
                ['field' => 'fields.{id}.country', 'change' => 'Changed', 'endpoints' => ['PUT /vaults/{vault}/contacts/{contact}/sections/{section}']],
                ['field' => 'fields.{id}.country_name', 'change' => 'Added', 'endpoints' => ['PUT /vaults/{vault}/contacts/{contact}/sections/{section}']],
            ],
        ],
    ],
];
