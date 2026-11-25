<?php

declare(strict_types=1);

return [
    'date' => '2026-11-25',
    'entries' => [
        [
            'id' => 'contact-template-change',
            'kind' => 'Added',
            'group' => 'Contacts',
            'title' => 'Put a contact on another template',
            'summary' => 'GET /vaults/{vault}/contacts/{contact}/template-preview says what putting a contact on another template would do: the answers that would go to Unused answers, those that would come back, and the reminders that would stop or run again. PUT /vaults/{vault}/contacts/{contact}/template makes the change. PUT /vaults/{vault}/contacts/{contact}/template-version is unchanged and still moves a contact between versions of its own template only.',
            'why' => 'A contact can now change template in the application, and everything the application does can be done through the API.',
            'breaking' => false,
            'changes' => [
                ['field' => 'template', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/contacts/{contact}/template-preview', 'PUT /vaults/{vault}/contacts/{contact}/template']],
                ['field' => 'structure_version_id', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/contacts/{contact}/template-preview', 'PUT /vaults/{vault}/contacts/{contact}/template']],
            ],
        ],
    ],
];
