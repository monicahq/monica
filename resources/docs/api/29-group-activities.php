<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();
$application = rtrim((string) config('app.url'), '/');

$vaultSlug = 'dunder-mifflin';
$groupId = '5d2a7c91-3e48-4b06-9f1d-a83c60e2b4f7';
$lifeEventId = '3b8e1d47-9a20-4c65-b7f3-d2a6905c8e14';

$webLink = 'The page of the contact the life event is recorded for, whose timeline shows it, in the application. It is the link to give a person.';

$lifeEvent = [
    'type' => 'life_event',
    'id' => $lifeEventId,
    'attributes' => [
        'name' => 'Budget review',
        'title' => 'Budget review',
        'life_event_type' => ['id' => 'b2f4d6a8-1c3e-4a5b-8d7f-0e9c2b4a6d81', 'name' => 'Meeting'],
        'group' => ['id' => $groupId, 'name' => 'Party planning committee'],
        'happened_on' => '2026-10-09',
        'date_precision' => 'day',
        'ended_on' => null,
        'happened_at_time' => '12:30',
        'timezone' => 'America/New_York',
        'duration_in_minutes' => 90,
        'narrative' => 'Angela went through every receipt twice.',
        'location' => 'Conference room',
        'participants' => [
            ['id' => '0d3f9b2e-6a41-4c7d-9e58-2b1a7c4e6f90', 'name' => 'Angela Martin', 'position' => 0, 'role' => null, 'notes' => null],
            ['id' => 'e5a8c1f3-7b29-4d06-8a4e-5c2d9f1b3a67', 'name' => 'Phyllis Vance', 'position' => 1, 'role' => null, 'notes' => null],
        ],
        'entity_participants' => [],
        'fields' => [
            ['field_id' => '412', 'key' => 'agenda', 'label' => 'Agenda', 'type' => 'text', 'row' => null, 'value' => 'Party budget', 'is_hidden' => false],
        ],
        'created_from' => 'group',
        'deleted_at' => null,
        'created_at' => 1791547200,
        'updated_at' => 1791547200,
    ],
    'links' => [
        'self' => $base.'/vaults/'.$vaultSlug.'/life-events/'.$lifeEventId,
        'web' => $application.'/vaults/'.$vaultSlug.'/contacts/angela-martin',
    ],
];

$flagNote = 'Groups are not yet available to every account. Until they are available to yours, every endpoint here answers 404.';

$groupIdentifier = [
    ['name' => 'vault', 'type' => 'string', 'required' => true, 'description' => 'The vault the group lives in, named either by the readable name it reports as slug or by its id.'],
    ['name' => 'group', 'type' => 'uuid', 'required' => true, 'description' => 'The id of the group. One addressed through the wrong vault, deleted, or belonging to another account, answers 404.'],
];

$lifeEventAttributes = [
    ['name' => 'name', 'type' => 'string', 'description' => 'What the life event is called: its title, or the name of its type when it has none.'],
    ['name' => 'title', 'type' => 'string', 'description' => 'The title written for it, or null.'],
    ['name' => 'life_event_type', 'type' => 'object', 'description' => 'Its type.'],
    ['name' => 'life_event_type.id', 'type' => 'uuid', 'description' => 'The id of the type, as the life event types endpoints give it.'],
    ['name' => 'life_event_type.name', 'type' => 'string', 'description' => 'What the type is called.'],
    ['name' => 'group', 'type' => 'object', 'description' => 'The group it was recorded on.'],
    ['name' => 'group.id', 'type' => 'uuid', 'description' => 'The id of the group.'],
    ['name' => 'group.name', 'type' => 'string', 'description' => 'What the group is called.'],
    ['name' => 'happened_on', 'type' => 'string', 'description' => 'The day it happened, written as a year, a month and a day.'],
    ['name' => 'date_precision', 'type' => 'string', 'description' => 'How precisely the day is known: day, month, year, decade or unknown. Below day, happened_on holds the first day of what is known.'],
    ['name' => 'ended_on', 'type' => 'string', 'description' => 'The day it ended, written as a year, a month and a day, or null for something that happened on one day.'],
    ['name' => 'happened_at_time', 'type' => 'string', 'description' => 'The time of day it happened, written as hours and minutes on a 24 hour clock, or null.'],
    ['name' => 'timezone', 'type' => 'string', 'description' => 'The time zone the time is written in, such as Europe/Paris, or null.'],
    ['name' => 'duration_in_minutes', 'type' => 'integer', 'description' => 'How long it lasted, in minutes, or null.'],
    ['name' => 'narrative', 'type' => 'string', 'description' => 'What was written about it, or null.'],
    ['name' => 'location', 'type' => 'string', 'description' => 'Where it happened, or null.'],
    ['name' => 'participants', 'type' => 'array', 'description' => 'The contacts who were there, in order. The first is the person the life event is recorded for.'],
    ['name' => 'participants[]', 'type' => 'object', 'description' => 'One contact who was there.'],
    ['name' => 'participants[].id', 'type' => 'uuid', 'description' => 'The id of the contact.'],
    ['name' => 'participants[].name', 'type' => 'string', 'description' => 'The name the contact is shown under.'],
    ['name' => 'participants[].position', 'type' => 'integer', 'description' => 'Where the contact stands in the list, counting from 0.'],
    ['name' => 'participants[].role', 'type' => 'string', 'description' => 'The part the contact took, such as best man, or null.'],
    ['name' => 'participants[].notes', 'type' => 'string', 'description' => 'What was written about the contact for this life event, or null.'],
    ['name' => 'entity_participants', 'type' => 'array', 'description' => 'The entities that were there, such as a pet or a car. Each holds its id (as the entities endpoints give it), its name, its position among everybody who was there counting from 0, its role and its notes, the last two possibly null. Empty when only contacts took part, and always empty right after a create, which takes contacts only.', 'opaque' => true],
    ['name' => 'fields', 'type' => 'array', 'description' => 'The values of the custom fields its type asks for. A field left empty is not listed.'],
    ['name' => 'fields[]', 'type' => 'object', 'description' => 'One value.'],
    ['name' => 'fields[].field_id', 'type' => 'string', 'description' => 'The id of the field, which is the key a value is written under when the life event is updated.'],
    ['name' => 'fields[].key', 'type' => 'string', 'description' => 'How the field is named in an import or an export. It is unique inside its group only.'],
    ['name' => 'fields[].label', 'type' => 'string', 'description' => 'What the field is called on the life event.'],
    ['name' => 'fields[].type', 'type' => 'string', 'description' => 'The kind of field: text, date, select, address and so on.'],
    ['name' => 'fields[].row', 'type' => 'integer', 'description' => 'For a field inside a repeating group, the id of the repetition it belongs to. Null otherwise.'],
    ['name' => 'fields[].value', 'type' => 'string', 'description' => 'The value, written as the contact page shows it. Null when hidden.'],
    ['name' => 'fields[].is_hidden', 'type' => 'boolean', 'description' => 'Whether the value was held back because the field is sensitive and the key does not hold the sensitive-fields scope.'],
    ['name' => 'created_from', 'type' => 'string', 'description' => 'Where it was written from. A life event recorded here says group.'],
    ['name' => 'deleted_at', 'type' => 'timestamp', 'description' => 'When it was deleted, as a count of seconds since 1970, or null.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When it was written down, as a count of seconds since 1970.'],
    ['name' => 'updated_at', 'type' => 'timestamp', 'description' => 'When it last changed, as a count of seconds since 1970.'],
];

$writeParams = [
    ['name' => 'life_event_type', 'type' => 'uuid', 'required' => true, 'description' => 'The id of its type, as the life event types endpoints give it. A type switched off cannot be chosen and answers 422.', 'example' => 'b2f4d6a8-1c3e-4a5b-8d7f-0e9c2b4a6d81'],
    ['name' => 'happened_on', 'type' => 'string', 'required' => true, 'description' => 'The day it happened, written as a year, a month and a day.', 'example' => '2026-10-09'],
    ['name' => 'participants', 'type' => 'array', 'required' => true, 'description' => 'The contacts who were there, in order, each named by its slug or its id, at least one. The first is the person the life event is recorded for. A contact of another vault answers 404.', 'example' => ['angela-martin', 'phyllis-vance']],
    ['name' => 'title', 'type' => 'string', 'required' => false, 'description' => 'A title, up to 255 characters. Without one, the life event is called by the name of its type.', 'example' => 'Budget review'],
    ['name' => 'narrative', 'type' => 'string', 'required' => false, 'description' => 'What happened, up to 65,535 characters.', 'example' => 'Angela went through every receipt twice.'],
    ['name' => 'location', 'type' => 'string', 'required' => false, 'description' => 'Where it happened, up to 255 characters.', 'example' => 'Conference room'],
    ['name' => 'duration_in_minutes', 'type' => 'integer', 'required' => false, 'description' => 'How long it lasted, from 1 to 525,600 minutes (one year).', 'example' => 90],
    ['name' => 'fields', 'type' => 'object', 'required' => false, 'description' => 'The values of the custom fields its type asks for, keyed by field id, in the same way as when a life event is recorded. A file field cannot be written here and answers 422.', 'example' => ['412' => 'Party budget']],
];

return [
    'name' => 'Group life events',
    'description' => 'Things that happened to a group as a whole: a meeting, an outing, a dinner. Each is a life event recorded on the group, so it shows on the group page and on the timeline of everybody who was there. To change or delete one, use the life events endpoints.',
    'sections' => [
        [
            'id' => 'group-life-events-index',
            'flag' => 'groups',
            'returnFields' => ApiDocsFields::page('life_event', 'life event', 'life events', $lifeEventAttributes, webLink: $webLink),
            'title' => 'List the life events of a group',
            'label' => 'List life events',
            'method' => 'GET',
            'path' => '/vaults/{vault}/groups/{group}/life-events',
            'examplePath' => '/vaults/'.$vaultSlug.'/groups/'.$groupId.'/life-events',
            'description' => 'List the life events recorded on a group, the most recent first.',
            'body' => [
                $flagNote,
                'The value of a sensitive field comes back as null, with is_hidden set to true, unless the key holds the sensitive-fields scope.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $groupIdentifier,
            'queryParams' => [
                ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Which page to answer with.', 'default' => '1', 'example' => 2],
                ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'How many records one page holds, between 1 and 100. A number outside the range is brought back into it rather than refused.', 'default' => '25', 'example' => 10],
            ],
            'returns' => 'A page of life event objects, and how many there are in all. A group of another account answers 404.',
            'response' => [
                'data' => [$lifeEvent],
                'meta' => ['page' => 1, 'per_page' => 25, 'total' => 1],
            ],
        ],
        [
            'id' => 'group-life-events-create',
            'flag' => 'groups',
            'returnFields' => ApiDocsFields::one('life_event', 'life event', $lifeEventAttributes, webLink: $webLink),
            'title' => 'Record a life event on a group',
            'label' => 'Record a life event',
            'method' => 'POST',
            'path' => '/vaults/{vault}/groups/{group}/life-events',
            'examplePath' => '/vaults/'.$vaultSlug.'/groups/'.$groupId.'/life-events',
            'description' => 'Write down something that happened to a group. It shows on the group page and goes on the timeline of everybody who was there, and the change is written to the account log.',
            'body' => [
                $flagNote,
                'The ids of the custom fields come from GET /life-event-types/{lifeEventType}/fields.',
                'A refusal names what it is about: life_event_type, happened_on, participants (missing or empty), a field under fields, or fields as a whole when a required field is left empty.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $groupIdentifier,
            'bodyParams' => $writeParams,
            'returns' => 'The life event object that was recorded, with the group it belongs to.',
            'responseStatus' => 201,
            'response' => ['data' => $lifeEvent],
        ],
    ],
];
