<?php

declare(strict_types=1);

return [
    'date' => '2026-11-20',
    'entries' => [
        [
            'id' => 'template-destroy-last-contact-template',
            'kind' => 'Changed',
            'group' => 'Templates',
            'title' => 'The last contact template stays',
            'summary' => 'Deleting the last contact template of the account answers 422. A vault starting new records from a deleted template now moves to the next template the product supplied, or to one of the account’s own, rather than losing its default. The refusal for a template still rendering records no longer says how many records are on it.',
            'why' => 'Deleting Basic contact left vaults with no template for new contacts, and no new vault could be created, although other contact templates were published.',
            'breaking' => false,
            'changes' => [
                ['field' => 'DELETE /templates/{template}', 'change' => 'Changed', 'endpoints' => ['DELETE /templates/{template}']],
            ],
        ],
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
