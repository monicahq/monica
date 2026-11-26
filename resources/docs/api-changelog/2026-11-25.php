<?php

declare(strict_types=1);

return [
    'date' => '2026-11-25',
    'entries' => [
        [
            'id' => 'vault-trash',
            'kind' => 'Added',
            'group' => 'Trash',
            'title' => 'List, restore and delete for good what waits in the trash of a vault',
            'summary' => 'Whatever is deleted from a vault now waits 30 days in its trash. List it, restore one item with everything deleted at the same time as it, delete one item for good, or empty the trash. Deleting a person now also sends their relationships, group memberships, reminders and the life events nobody else takes part in to the trash, and deleting a group its memberships, notes, files and reminders. A record of an entity type can now be deleted.',
            'why' => 'Vault settings now have a Trash tab, and everything the application does can be done through the API.',
            'breaking' => false,
            'changes' => [
                ['field' => 'trash_item', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/trash', 'PUT /vaults/{vault}/trash/{kind}/{item}/restoration', 'DELETE /vaults/{vault}/trash/{kind}/{item}', 'DELETE /vaults/{vault}/trash']],
                ['field' => 'entity_deletion', 'change' => 'Added', 'endpoints' => ['DELETE /vaults/{vault}/entities/{entity}']],
            ],
        ],
        [
            'id' => 'contacts-bulk-actions',
            'kind' => 'Added',
            'group' => 'Contacts',
            'title' => 'Delete several contacts, or set their categories, in one call',
            'summary' => 'Delete up to 500 contacts of a vault at once, each as deleting it alone would, with the contact representing you kept and listed with the reason. Set the categories of up to 500 contacts at once, each ending with exactly the categories listed for it, with the contacts whose template has no category field listed with the reason.',
            'why' => 'The people screen can now select several contacts and delete them or change their category, and everything the application does can be done through the API.',
            'breaking' => false,
            'changes' => [
                ['field' => 'contact_deletion', 'change' => 'Added', 'endpoints' => ['DELETE /vaults/{vault}/contacts']],
                ['field' => 'contact_category_change', 'change' => 'Added', 'endpoints' => ['PUT /vaults/{vault}/contacts/categories']],
            ],
        ],
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
            'id' => 'value-list-writes',
            'kind' => 'Added',
            'group' => 'Templates',
            'title' => 'Create, rename, reorder and delete value lists and their values',
            'summary' => 'Create, rename and delete a value list. Add, rename and delete a value, stop offering it or offer it again, and move it in its list. A value of GET /value-lists now also carries is_active and position. A list the application itself reads refuses deletion, a value in use refuses deletion, and a list that takes no choices of its own refuses a new value, each with 422.',
            'why' => 'The settings screen could change a value list and the API could only read one.',
            'breaking' => false,
            'changes' => [
                ['field' => 'value_list', 'change' => 'Added', 'endpoints' => ['POST /value-lists', 'PUT /value-lists/{valueList}', 'DELETE /value-lists/{valueList}']],
                ['field' => 'value_list_item', 'change' => 'Added', 'endpoints' => ['POST /value-lists/{valueList}/items', 'PUT /value-lists/{valueList}/items/{valueListItem}', 'DELETE /value-lists/{valueList}/items/{valueListItem}', 'PUT /value-lists/{valueList}/items/{valueListItem}/activation', 'PUT /value-lists/{valueList}/items/{valueListItem}/position']],
                ['field' => 'items[].is_active, items[].position', 'change' => 'Added', 'endpoints' => ['GET /value-lists']],
            ],
        ],
        [
            'id' => 'field-values-refuse-unknown-keys',
            'kind' => 'Changed',
            'group' => 'Contacts',
            'title' => 'Field values refuse a key they do not know',
            'summary' => 'Writing field values now answers 422 on fields for a field that is not in the section or template, for a key of a repeated row other than id, values, labels, label_wordings and unknown_year, and for a key of values that is not a child of the repeated field. Nothing is written when the call is refused. The unknown_year of a row is now honoured for a child date.',
            'why' => 'A row sent with the child values beside values instead of inside it was answered 200 and nothing was written.',
            'breaking' => true,
            'impact' => 'An integration that sent extra keys and relied on them being ignored now gets a 422. Send each row as {"values": {"<child id>": ...}}.',
            'changes' => [
                ['field' => 'fields', 'change' => 'Changed', 'endpoints' => ['PUT /vaults/{vault}/contacts/{contact}/sections/{section}', 'POST /vaults/{vault}/entity-types/{entityType}/entities', 'PUT /vaults/{vault}/entities/{entity}', 'POST /vaults/{vault}/life-events', 'PUT /vaults/{vault}/life-events/{lifeEvent}', 'POST /vaults/{vault}/groups/{group}/life-events']],
            ],
        ],
        [
            'id' => 'duration-up-to-a-year',
            'kind' => 'Changed',
            'group' => 'Life events',
            'title' => 'A duration goes up to a year',
            'summary' => 'A duration field and duration_in_minutes now take 1 to 525600 minutes, one year, where they stopped at 100000.',
            'why' => 'About 69 days was too short for a duration somebody wanted to record.',
            'breaking' => false,
            'changes' => [
                ['field' => 'duration_in_minutes', 'change' => 'Changed', 'endpoints' => ['POST /vaults/{vault}/life-events', 'PUT /vaults/{vault}/life-events/{lifeEvent}', 'POST /vaults/{vault}/groups/{group}/life-events']],
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
