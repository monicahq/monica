<?php

declare(strict_types=1);

return [
    'date' => '2026-11-25',
    'entries' => [
        [
            'id' => 'changelog-entry-screenshots',
            'kind' => 'Added',
            'group' => 'What’s new',
            'title' => 'Read the screenshots of a change',
            'summary' => 'A changelog entry now carries screenshots, a list of the screenshots that show the change, in order. Each one gives its position, the absolute url of a WebP image, a description in your language, and its width and height in pixels. The list is empty when the change has none.',
            'why' => 'What’s new now shows screenshots under a change, and everything the application shows can be read through the API.',
            'breaking' => false,
            'changes' => [
                ['field' => 'screenshots', 'change' => 'Added', 'endpoints' => ['GET /changelog-entries']],
            ],
        ],
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
        [
            'id' => 'record-statistics-and-template-changes',
            'kind' => 'Added',
            'group' => 'Record statistics',
            'title' => 'Count records by template, and change the template of many at once',
            'summary' => 'GET /vaults/{vault}/record-kinds/{recordKind}/statistics counts the records of one kind in a vault by template and version. GET /vaults/{vault}/record-kinds/{recordKind}/statistics/fields says how many records fill in each field of a template. GET /vaults/{vault}/record-kinds/{recordKind}/template-change-preview says what moving every record of one template onto another would do, POST /vaults/{vault}/record-kinds/{recordKind}/template-changes starts that move in the background, and GET /vaults/{vault}/template-changes/{templateChange} says how far it is. A kind of record is contacts or the id of an entity type.',
            'why' => 'The application now shows these statistics and moves many records at once, and everything the application does can be done through the API.',
            'breaking' => false,
            'changes' => [
                ['field' => 'record kinds', 'change' => 'Added', 'endpoints' => [
                    'GET /vaults/{vault}/record-kinds/{recordKind}/statistics',
                    'GET /vaults/{vault}/record-kinds/{recordKind}/statistics/fields',
                    'GET /vaults/{vault}/record-kinds/{recordKind}/template-change-preview',
                    'POST /vaults/{vault}/record-kinds/{recordKind}/template-changes',
                    'GET /vaults/{vault}/template-changes/{templateChange}',
                ]],
            ],
        ],
    ],
];
