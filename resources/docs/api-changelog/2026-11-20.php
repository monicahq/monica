<?php

declare(strict_types=1);

return [
    'date' => '2026-11-20',
    'entries' => [
        [
            'id' => 'last-interaction-at-recorded',
            'kind' => 'Changed',
            'group' => 'Contacts',
            'title' => 'Last in touch is recorded',
            'summary' => 'A contact\'s last_interaction_at now holds the day of the latest life event they took part in that has already happened and whose nature has the new updates_last_in_touch. Natures carry that setting, readable and changeable, and the supplied ones answer false for news about one person such as a move.',
            'why' => 'Nothing recorded the date, so it was null for every contact whatever life events they had.',
            'breaking' => false,
            'changes' => [
                ['field' => 'last_interaction_at', 'change' => 'Changed', 'endpoints' => ['GET /vaults/{vault}/contacts', 'GET /vaults/{vault}/contacts/{contact}']],
                ['field' => 'updates_last_in_touch', 'change' => 'Added', 'endpoints' => ['GET /life-event-types', 'GET /life-event-types/{lifeEventType}', 'POST /life-event-types', 'PUT /life-event-types/{lifeEventType}']],
            ],
        ],
        [
            'id' => 'vault-create-owner-on-chosen-template',
            'kind' => 'Changed',
            'group' => 'Vaults',
            'title' => 'Your contact starts on the template you choose',
            'summary' => 'The contact standing for you, created with a new vault, now starts from the structure_version_id the request names. It used to start from Basic contact, and only the contacts added afterwards used the version chosen.',
            'why' => 'The first contact of a new vault ignored the template chosen for it.',
            'breaking' => false,
            'changes' => [
                ['field' => 'structure_version_id', 'change' => 'Changed', 'endpoints' => ['POST /vaults']],
            ],
        ],
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
