<?php

declare(strict_types=1);

return [
    'date' => '2026-10-30',
    'note' => 'One change breaks: updated_at is no longer null on a record that never changed.',
    'entries' => [
        [
            'id' => 'updated-at-equals-created-at',
            'kind' => 'Changed',
            'group' => 'Contacts',
            'title' => 'updated_at equals created_at until the first change',
            'summary' => 'On a record that has never changed, updated_at is now equal to created_at instead of null. This applies to every resource listed here: the profile, vaults, contacts, entity types, templates and their versions, relationship categories and types, life event categories and types, and relationships.',
            'why' => 'A field that is always a timestamp is simpler to read and to sort on.',
            'breaking' => true,
            'impact' => 'An integration reading a null updated_at as "never changed" must now compare it with created_at.',
            'changes' => [
                ['field' => 'updated_at', 'change' => 'Changed', 'endpoints' => ['GET /me', 'PUT /me', 'PUT /me/contact-list-view', 'PUT /me/welcome', 'PUT /me/full-access-offer', 'PUT /me/timezone', 'GET /vaults', 'GET /vaults/{vault}', 'POST /vaults', 'PUT /vaults/{vault}', 'GET /vaults/{vault}/contacts', 'GET /vaults/{vault}/contacts/{contact}', 'GET /vaults/{vault}/contacts/me', 'POST /vaults/{vault}/contacts', 'PUT /vaults/{vault}/contacts/{contact}/name', 'PUT /vaults/{vault}/contacts/{contact}/name-representations', 'PUT /vaults/{vault}/contacts/{contact}/favourite', 'POST /vaults/{vault}/contacts/{contact}/avatar', 'DELETE /vaults/{vault}/contacts/{contact}/avatar', 'PUT /vaults/{vault}/contacts/{contact}/template-version', 'PUT /vaults/{vault}/contacts/{contact}/sections/{section}', 'GET /entity-types', 'GET /entity-types/{entityType}', 'POST /entity-types', 'PUT /entity-types/{entityType}', 'PUT /entity-types/{entityType}/activation', 'PUT /entity-types/{entityType}/position', 'GET /vaults/{vault}/entity-types/{entityType}/entities', 'POST /vaults/{vault}/entity-types/{entityType}/entities', 'GET /templates', 'GET /templates/{template}', 'POST /templates', 'PUT /templates/{template}', 'PUT /templates/{template}/activation', 'PUT /templates/{template}/position', 'POST /templates/{template}/duplicate', 'GET /templates/{template}/versions', 'POST /templates/{template}/versions', 'PUT /templates/{template}/versions/{version}/publication', 'GET /templates/{template}/versions/{version}', 'POST /templates/{template}/versions/{version}/sections/{section}/fields', 'PUT /templates/{template}/versions/{version}/sections/{section}/fields/{field}', 'PUT /templates/{template}/versions/{version}/sections/{section}/fields/{field}/position', 'GET /relationship-categories', 'GET /relationship-categories/{relationshipCategory}', 'POST /relationship-categories', 'PUT /relationship-categories/{relationshipCategory}', 'PUT /relationship-categories/{relationshipCategory}/activation', 'PUT /relationship-categories/{relationshipCategory}/position', 'GET /relationship-types', 'GET /relationship-categories/{relationshipCategory}/types', 'GET /relationship-types/{relationshipType}', 'POST /relationship-types', 'PUT /relationship-types/{relationshipType}', 'PUT /relationship-types/{relationshipType}/activation', 'PUT /relationship-types/{relationshipType}/position', 'GET /life-event-categories', 'GET /life-event-categories/{lifeEventCategory}', 'GET /life-event-categories/{lifeEventCategory}/types', 'POST /life-event-categories', 'PUT /life-event-categories/{lifeEventCategory}', 'PUT /life-event-categories/{lifeEventCategory}/activation', 'PUT /life-event-categories/{lifeEventCategory}/position', 'GET /life-event-types', 'GET /life-event-types/{lifeEventType}', 'POST /life-event-types', 'PUT /life-event-types/{lifeEventType}', 'PUT /life-event-types/{lifeEventType}/activation', 'PUT /life-event-types/{lifeEventType}/position', 'GET /life-event-types/{lifeEventType}/fields', 'PUT /life-event-types/{lifeEventType}/fields', 'GET /vaults/{vault}/contacts/{contact}/relationships', 'POST /vaults/{vault}/contacts/{contact}/relationships', 'GET /vaults/{vault}/contacts/{contact}/relationships/{relationship}', 'PUT /vaults/{vault}/contacts/{contact}/relationships/{relationship}']],
            ],
            'example' => [
                'label' => 'GET contacts/{contact}, a contact never changed',
                'before' => '{ "created_at": 1756080000, "updated_at": null }',
                'after' => '{ "created_at": 1756080000, "updated_at": 1756080000 }',
            ],
        ],
    ],
];
