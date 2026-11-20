<?php

declare(strict_types=1);

return [
    'date' => '2026-11-20',
    'entries' => [
        [
            'id' => 'number-field-range',
            'kind' => 'Changed',
            'group' => 'Contacts',
            'title' => 'A number field refuses a number it cannot hold',
            'summary' => 'The value of a number field is now a number from -99999999999999 to 99999999999999. A larger one answers 422 on that field and writes nothing.',
            'why' => 'A larger number passed validation and then failed when it was stored, so the request answered 500.',
            'breaking' => false,
            'changes' => [
                ['field' => 'value of a number field', 'change' => 'Changed', 'endpoints' => ['PUT /vaults/{vault}/contacts/{contact}/sections/{section}', 'POST /vaults/{vault}/life-events', 'PUT /vaults/{vault}/life-events/{lifeEvent}', 'POST /vaults/{vault}/groups/{group}/life-events', 'POST /vaults/{vault}/entity-types/{entityType}/entities']],
            ],
        ],
    ],
];
