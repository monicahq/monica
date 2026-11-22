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
        [
            'id' => 'contact-summaries-listed',
            'kind' => 'Added',
            'group' => 'Contacts',
            'title' => 'A lightweight list of every contact of a vault',
            'summary' => 'GET /vaults/{vault}/contact-summaries lists every contact of a vault with only its id, its name, whether it is partial and whether it is archived, up to five hundred a page. Partial and full contacts come back together, filter keeps one kind, and archived=included adds the archived contacts.',
            'why' => 'Listing everybody meant several heavy pages of the contact list, which never returns partial and full contacts together and never returns archived ones.',
            'breaking' => false,
            'changes' => [
                ['field' => 'contact-summaries', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/contact-summaries']],
            ],
        ],
        [
            'id' => 'section-date-reminders',
            'kind' => 'Added',
            'group' => 'Contacts',
            'title' => 'A reminder on a date, such as a birthday',
            'summary' => 'Saving a section of a contact now takes reminders, keyed by the id of a date field: an object turns on or changes the reminder of that date, and null takes it away. The date does not have to be sent again. A date left out of reminders keeps its reminder.',
            'why' => 'A reminder on a date could only be turned on from the page, and saving a date through the API took away the reminder it carried.',
            'breaking' => false,
            'changes' => [
                ['field' => 'reminders', 'change' => 'Added', 'endpoints' => ['PUT /vaults/{vault}/contacts/{contact}/sections/{section}']],
            ],
        ],
    ],
];
