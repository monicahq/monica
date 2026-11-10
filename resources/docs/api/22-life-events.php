<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();
$application = rtrim((string) config('app.url'), '/');

$webLink = 'The page of the contact the life event is recorded for, whose timeline shows it, in the application. It is the link to give a person.';

$vaultSlug = 'dunder-mifflin';
$lifeEventId = '7c1e5a92-4b3d-4f80-a6e2-93d0b1c4f857';

$lifeEvent = [
    'type' => 'life_event',
    'id' => $lifeEventId,
    'attributes' => [
        'name' => 'Lunch at Cugino’s',
        'title' => 'Lunch at Cugino’s',
        'life_event_type' => ['id' => 'b2f4d6a8-1c3e-4a5b-8d7f-0e9c2b4a6d81', 'name' => 'Meal'],
        'group' => null,
        'happened_on' => '2026-10-09',
        'duration_in_minutes' => 60,
        'narrative' => 'Michael insisted on paying, then asked for the receipt.',
        'location' => 'Scranton',
        'participants' => [
            ['id' => '0d3f9b2e-6a41-4c7d-9e58-2b1a7c4e6f90', 'name' => 'Pam Beesly', 'position' => 0],
            ['id' => 'e5a8c1f3-7b29-4d06-8a4e-5c2d9f1b3a67', 'name' => 'Jim Halpert', 'position' => 1],
        ],
        'fields' => [
            ['field_id' => '412', 'key' => 'dish', 'label' => 'Dish', 'type' => 'text', 'row' => null, 'value' => 'Chicken parm', 'is_hidden' => false],
        ],
        'created_from' => 'api',
        'deleted_at' => null,
        'created_at' => 1791547200,
        'updated_at' => 1791547200,
    ],
    'links' => [
        'self' => $base.'/vaults/'.$vaultSlug.'/life-events/'.$lifeEventId,
        'web' => $application.'/vaults/'.$vaultSlug.'/contacts/pam-beesly',
    ],
];

$vaultIdentifier = [
    ['name' => 'vault', 'type' => 'string', 'required' => true, 'description' => 'The vault the life events live in, named either by the readable name it reports as slug or by the identifier it reports as id.'],
];

$lifeEventIdentifier = [
    ...$vaultIdentifier,
    ['name' => 'lifeEvent', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of the life event. One addressed through the wrong vault, or belonging to another account, answers 404.'],
];

$lifeEventAttributes = [
    ['name' => 'name', 'type' => 'string', 'description' => 'What the life event is called: its title, or the name of its type when it has none.'],
    ['name' => 'title', 'type' => 'string', 'description' => 'The title written for it, or null.'],
    ['name' => 'life_event_type', 'type' => 'object', 'description' => 'Its type. A type deleted since still names the life events recorded as it.'],
    ['name' => 'life_event_type.id', 'type' => 'uuid', 'description' => 'The identifier of the type, as the life event types endpoints give it.'],
    ['name' => 'life_event_type.name', 'type' => 'string', 'description' => 'What the type is called.'],
    ['name' => 'group', 'type' => 'object', 'description' => 'The group it was recorded on, as an object holding its id and its name, or null when it was not recorded on a group or the group has been deleted.'],
    ['name' => 'happened_on', 'type' => 'string', 'description' => 'The day it happened, written as a year, a month and a day.'],
    ['name' => 'duration_in_minutes', 'type' => 'integer', 'description' => 'How long it lasted, in minutes, or null.'],
    ['name' => 'narrative', 'type' => 'string', 'description' => 'What was written about it, or null.'],
    ['name' => 'location', 'type' => 'string', 'description' => 'Where it happened, or null.'],
    ['name' => 'participants', 'type' => 'array', 'description' => 'The contacts who were there, in order. The first is the person the life event is recorded for.'],
    ['name' => 'participants[]', 'type' => 'object', 'description' => 'One contact who was there.'],
    ['name' => 'participants[].id', 'type' => 'uuid', 'description' => 'The identifier of the contact.'],
    ['name' => 'participants[].name', 'type' => 'string', 'description' => 'The name the contact is shown under.'],
    ['name' => 'participants[].position', 'type' => 'integer', 'description' => 'Where the contact stands in the list, counting from 0.'],
    ['name' => 'fields', 'type' => 'array', 'description' => 'The values of the custom fields its type asks for. A field left empty is not listed.'],
    ['name' => 'fields[]', 'type' => 'object', 'description' => 'One value.'],
    ['name' => 'fields[].field_id', 'type' => 'string', 'description' => 'The identifier of the field, which is the key a value is written under when the life event is updated.'],
    ['name' => 'fields[].key', 'type' => 'string', 'description' => 'How the field is named in an import or an export. It is unique inside its group only.'],
    ['name' => 'fields[].label', 'type' => 'string', 'description' => 'What the field is called on the life event.'],
    ['name' => 'fields[].type', 'type' => 'string', 'description' => 'The kind of field: text, date, select, address and so on.'],
    ['name' => 'fields[].row', 'type' => 'integer', 'description' => 'For a field inside a repeating group, the identifier of the repetition it belongs to, which is the id an update keeps that repetition by. Null otherwise.'],
    ['name' => 'fields[].value', 'type' => 'string', 'description' => 'The value, written as the contact page shows it. A file field gives the name of the file. Null when hidden.'],
    ['name' => 'fields[].is_hidden', 'type' => 'boolean', 'description' => 'Whether the value was held back because the field is sensitive and the key does not hold the sensitive-fields scope.'],
    ['name' => 'created_from', 'type' => 'string', 'description' => 'Where it was written from: contact, dashboard, group, api, mcp, day, import or module.'],
    ['name' => 'deleted_at', 'type' => 'timestamp', 'description' => 'When it was deleted, as a count of seconds since 1970, or null.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When it was written down, as a count of seconds since 1970.'],
    ['name' => 'updated_at', 'type' => 'timestamp', 'description' => 'When it last changed, as a count of seconds since 1970.'],
];

$writeParams = [
    ['name' => 'life_event_type', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of its type, as the life event types endpoints give it. A type switched off cannot be chosen for a new life event and answers 422.', 'example' => 'b2f4d6a8-1c3e-4a5b-8d7f-0e9c2b4a6d81'],
    ['name' => 'happened_on', 'type' => 'string', 'required' => true, 'description' => 'The day it happened, written as a year, a month and a day.', 'example' => '2026-10-09'],
    ['name' => 'participants', 'type' => 'array', 'required' => true, 'description' => 'The contacts who were there, in order, each named by its slug or its id. The first is the person the life event is recorded for. A contact of another vault answers 404.', 'example' => ['pam-beesly', 'jim-halpert']],
    ['name' => 'title', 'type' => 'string', 'required' => false, 'description' => 'A title, up to 255 characters. Without one, the life event is called by the name of its type.', 'example' => 'Lunch at Cugino’s'],
    ['name' => 'narrative', 'type' => 'string', 'required' => false, 'description' => 'What happened, up to 65,535 characters.', 'example' => 'Michael insisted on paying, then asked for the receipt.'],
    ['name' => 'location', 'type' => 'string', 'required' => false, 'description' => 'Where it happened, up to 255 characters.', 'example' => 'Scranton'],
    ['name' => 'duration_in_minutes', 'type' => 'integer', 'required' => false, 'description' => 'How long it lasted, from 1 to 100,000 minutes.', 'example' => 60],
    ['name' => 'fields', 'type' => 'object', 'required' => false, 'description' => 'The values of the custom fields its type asks for, keyed by field id. A text field takes a string, a select field the id of its choice, a multiple select field a list of them, a date a year, a month and a day. A repeating group takes a list of repetitions, each holding its id when it already exists and its values keyed by the ids of the fields inside the group. A file field cannot be written here and answers 422.', 'example' => ['412' => 'Chicken parm']],
];

$fieldIdsNote = 'The ids of the custom fields come from GET /life-event-types/{lifeEventType}/fields for a new life event, and from the fields of the life event itself once it exists. A field key cannot stand for an id, since a key is only unique inside its group.';

$sensitiveNote = 'The value of a sensitive field comes back as null, with is_hidden set to true, unless the key holds the sensitive-fields scope.';

return [
    'name' => 'Life events',
    'description' => 'What happened, on a date, with people: a call, a meal, a move, a wedding. Recording one puts it on the timeline of everybody who was there.',
    'sections' => [
        [
            'id' => 'life-events-index',
            'returnFields' => ApiDocsFields::page('life_event', 'life event', 'life events', $lifeEventAttributes, webLink: $webLink),
            'title' => 'List the life events of a vault',
            'label' => 'List life events',
            'method' => 'GET',
            'path' => '/vaults/{vault}/life-events',
            'examplePath' => '/vaults/'.$vaultSlug.'/life-events',
            'description' => 'List the life events of a vault, the most recent first.',
            'body' => [
                'Deleted life events are left out. Narrow the list to one person with contact.',
                $sensitiveNote,
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $vaultIdentifier,
            'queryParams' => [
                ['name' => 'contact', 'type' => 'string', 'required' => false, 'description' => 'Only the life events this contact took part in, named by its slug or its id. A contact of another vault answers 404.'],
                ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Which page to answer with.', 'default' => '1'],
                ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'How many records one page holds, between 1 and 100. A number outside the range is brought back into it rather than refused.', 'default' => '25'],
            ],
            'returns' => 'A page of life event objects, and how many there are in all. A vault of another account answers 404.',
            'response' => [
                'data' => [$lifeEvent],
                'meta' => ['page' => 1, 'per_page' => 25, 'total' => 1],
            ],
        ],
        [
            'id' => 'life-events-show',
            'returnFields' => ApiDocsFields::one('life_event', 'life event', $lifeEventAttributes, webLink: $webLink),
            'title' => 'Get a life event',
            'label' => 'Get a life event',
            'method' => 'GET',
            'path' => '/vaults/{vault}/life-events/{lifeEvent}',
            'examplePath' => '/vaults/'.$vaultSlug.'/life-events/'.$lifeEventId,
            'description' => 'Retrieve one life event, with who was there and the values of its custom fields.',
            'body' => [
                $sensitiveNote,
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $lifeEventIdentifier,
            'returns' => 'A life event object. One of another account or vault answers 404.',
            'response' => ['data' => $lifeEvent],
        ],
        [
            'id' => 'life-events-create',
            'returnFields' => ApiDocsFields::one('life_event', 'life event', $lifeEventAttributes, webLink: $webLink),
            'title' => 'Record a life event',
            'label' => 'Record a life event',
            'method' => 'POST',
            'path' => '/vaults/{vault}/life-events',
            'examplePath' => '/vaults/'.$vaultSlug.'/life-events',
            'description' => 'Write down something that happened. It goes on the timeline of everybody who was there, exactly as if it had been written on the page of the first of them.',
            'body' => [
                $fieldIdsNote,
                'A refusal names what it is about: life_event_type, happened_on, participants, a field under fields, or fields as a whole when a required field is left empty.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $vaultIdentifier,
            'bodyParams' => $writeParams,
            'returns' => 'The life event object that was recorded.',
            'responseStatus' => 201,
            'response' => ['data' => $lifeEvent],
        ],
        [
            'id' => 'life-events-update',
            'returnFields' => ApiDocsFields::one('life_event', 'life event', $lifeEventAttributes, webLink: $webLink),
            'title' => 'Change a life event',
            'label' => 'Change a life event',
            'method' => 'PUT',
            'path' => '/vaults/{vault}/life-events/{lifeEvent}',
            'examplePath' => '/vaults/'.$vaultSlug.'/life-events/'.$lifeEventId,
            'description' => 'Replace what a life event says: its type, its date, its text and who was there.',
            'body' => [
                'Every attribute is replaced, so one left out is cleared. The custom field values are the exception: leave fields out and every value stays as it is. Send fields and it replaces the whole set, so a field missing from it is cleared, and a repetition sent without its id replaces the one it stood for. A sensitive field the key cannot see keeps its value unless it is sent.',
                'The first participant has to stay the person the life event is recorded for, and anything else answers 422. To record it for somebody else, delete it and record it again.',
                'A life event keeps the version of its type\'s fields it was written with while its type stays the same. Choosing another type moves it to that type\'s fields and lets the old values go.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $lifeEventIdentifier,
            'bodyParams' => $writeParams,
            'returns' => 'The life event object as it stands now.',
            'response' => ['data' => $lifeEvent],
        ],
        [
            'id' => 'life-events-destroy',
            'title' => 'Delete a life event',
            'label' => 'Delete a life event',
            'method' => 'DELETE',
            'path' => '/vaults/{vault}/life-events/{lifeEvent}',
            'examplePath' => '/vaults/'.$vaultSlug.'/life-events/'.$lifeEventId,
            'description' => 'Take a life event off the timeline of everybody who was there. It can be restored.',
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $lifeEventIdentifier,
            'returns' => 'Nothing at all.',
            'responseStatus' => 204,
        ],
        [
            'id' => 'life-events-restoration-update',
            'returnFields' => ApiDocsFields::one('life_event', 'life event', $lifeEventAttributes, webLink: $webLink),
            'title' => 'Restore a life event',
            'label' => 'Restore a life event',
            'method' => 'PUT',
            'path' => '/vaults/{vault}/life-events/{lifeEvent}/restoration',
            'examplePath' => '/vaults/'.$vaultSlug.'/life-events/'.$lifeEventId.'/restoration',
            'description' => 'Put a deleted life event back on the timeline of everybody who was there.',
            'body' => [
                'A life event that is not deleted answers 404.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $lifeEventIdentifier,
            'returns' => 'The life event object, restored.',
            'response' => ['data' => $lifeEvent],
        ],
    ],
];
