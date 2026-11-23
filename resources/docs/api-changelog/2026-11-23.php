<?php

declare(strict_types=1);

return [
    'date' => '2026-11-23',
    'entries' => [
        [
            'id' => 'contact-partial-state-set',
            'kind' => 'Added',
            'group' => 'Contacts',
            'title' => 'Make a contact partial, or full again',
            'summary' => 'PUT /vaults/{vault}/contacts/{contact}/partial takes partial, true or false, and returns the contact. A full contact made partial leaves the list of people and the count of the free plan, and nothing written about them changes. A partial contact made full counts again, within the plan.',
            'why' => 'A contact could only be made partial when it was created, and made full again only through a relationship or a merge.',
            'breaking' => false,
            'changes' => [
                ['field' => 'partial', 'change' => 'Added', 'endpoints' => ['PUT /vaults/{vault}/contacts/{contact}/partial']],
            ],
        ],
        [
            'id' => 'merge-of-partial-contacts-counted',
            'kind' => 'Changed',
            'group' => 'Duplicates and merges',
            'title' => 'Merging two partial contacts respects the free plan',
            'summary' => 'Merging two partial contacts makes the survivor a full one. On the free plan, it now answers 422 under other when the account already holds every contact the plan allows.',
            'why' => 'The merge made a counted contact out of two uncounted ones without asking the plan, which let an account pass its ceiling.',
            'breaking' => false,
            'changes' => [
                ['field' => 'other', 'change' => 'Changed', 'endpoints' => ['POST /vaults/{vault}/contacts/{contact}/merge/{other}']],
            ],
        ],
    ],
];
