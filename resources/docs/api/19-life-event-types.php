<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();

$type = [
    'type' => 'life_event_type',
    'id' => 'c3f9a8d1-4e62-4b07-9a35-6d180b2fc74e',
    'attributes' => [
        'life_event_category_id' => '5f8b3c21-9d07-4a64-8e12-7b0a4d6c3e95',
        'name' => 'Call',
        'description' => null,
        'icon' => 'activity',
        'colour' => null,
        'position' => 1,
        'is_system' => true,
        'is_active' => true,
        'created_at' => 1756252800,
        'updated_at' => 1756252800,
    ],
    'links' => [
        'self' => $base.'/life-event-types/c3f9a8d1-4e62-4b07-9a35-6d180b2fc74e',
    ],
];

$otherType = [
    'type' => 'life_event_type',
    'id' => '8b2e5c70-1a94-4f38-b6d2-0e7c94a35f18',
    'attributes' => [
        'life_event_category_id' => '5f8b3c21-9d07-4a64-8e12-7b0a4d6c3e95',
        'name' => 'Board game night',
        'description' => 'An evening around a table with a box of cardboard.',
        'icon' => 'activity',
        'colour' => 'bg-sky-500',
        'position' => 2,
        'is_system' => false,
        'is_active' => true,
        'created_at' => 1756339200,
        'updated_at' => 1756425600,
    ],
    'links' => [
        'self' => $base.'/life-event-types/8b2e5c70-1a94-4f38-b6d2-0e7c94a35f18',
    ],
];

$pageParams = [
    ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Which page to answer with.', 'default' => '1'],
    ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'How many natures one page holds, between 1 and 100. A number outside the range is brought back into it rather than refused.', 'default' => '25'],
];

$identifier = [
    ['name' => 'lifeEventType', 'type' => 'uuid', 'required' => true, 'description' => 'The id of the nature. A path that is not a uuid answers 404 without a query being run.'],
];

$typeAttributes = [
    ['name' => 'life_event_category_id', 'type' => 'uuid', 'description' => 'The category the nature is filed under. Moving a nature to another category is an ordinary change, and the life events recorded with it are regrouped along with it.'],
    ['name' => 'name', 'type' => 'string', 'description' => 'What the nature is called. One the product supplied reads in the language you ask for, until somebody renames it, after which their words stand in every language. Up to 255 characters, reduced to plain text, so no markup survives here.'],
    ['name' => 'description', 'type' => 'string', 'description' => 'A sentence saying what the nature covers, shown where somebody chooses one. Up to 255 characters, or null when nobody wrote one.'],
    ['name' => 'icon', 'type' => 'string', 'description' => 'The glyph drawn beside the nature, chosen from the catalogue the product ships, or null to read by name alone.'],
    ['name' => 'colour', 'type' => 'string', 'description' => 'The tint the nature is drawn in, chosen from the palette the product ships, or null to take the ordinary one.'],
    ['name' => 'position', 'type' => 'integer', 'description' => 'Where the nature sits inside its category, counting from 1. Retired natures are numbered along with the rest, so one switched back on finds the place it had.'],
    ['name' => 'is_system', 'type' => 'boolean', 'description' => 'Whether the product supplied the nature with the account. It governs one thing and one thing only: a supplied nature cannot be deleted. It may be renamed, redrawn, moved and retired like any other.'],
    ['name' => 'is_active', 'type' => 'boolean', 'description' => 'Whether the nature is offered when a life event is recorded. False takes it out of that choice and touches nothing already written.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the nature was added, as a count of seconds since 1970 rather than a written date.'],
    ['name' => 'updated_at', 'type' => 'timestamp', 'description' => 'When it last changed, as a count of seconds. Equal to created_at until it first changes.'],
];

$nested = function (string $prefix, string $type, string $singular, string $plural, array $attributes, string $summary): array {
    $rows = [
        ['name' => $prefix, 'type' => 'array', 'description' => $summary],
        ['name' => "{$prefix}[]", 'type' => 'object', 'description' => "One {$singular}."],
        ['name' => "{$prefix}[].type", 'type' => 'string', 'description' => "Always {$type}. It names the kind of record, so a caller holding several kinds can tell them apart."],
        ['name' => "{$prefix}[].id", 'type' => 'string', 'description' => "The id of the {$singular}, as a string even when it reads as a number."],
        ['name' => "{$prefix}[].attributes", 'type' => 'object', 'description' => "Everything the {$singular} holds."],
    ];

    foreach ($attributes as $attribute) {
        $rows[] = [...$attribute, 'name' => "{$prefix}[].attributes.{$attribute['name']}"];
    }

    return $rows;
};

$fieldAttributes = [
    ['name' => 'key', 'type' => 'string', 'description' => 'How the field is named in an import or an export, settled when the field is added. Send it back to keep a field rather than replace it.'],
    ['name' => 'type', 'type' => 'string', 'description' => 'What kind of thing the field holds: one of text, textarea, number, boolean, url, email, phone, select, multiselect, radio, date, recurring_date, relation, file, image, avatar, address, repeater, divider, heading or help. The last three carry no value and are there to lay the form out.'],
    ['name' => 'label', 'type' => 'string', 'description' => 'What the field is called on the form.'],
    ['name' => 'help_text', 'type' => 'string', 'description' => 'A sentence shown under the field, or null when there is none.'],
    ['name' => 'placeholder', 'type' => 'string', 'description' => 'What the empty box shows as an example, or null.'],
    ['name' => 'position', 'type' => 'integer', 'description' => 'Where the field sits in its group, counting from 1.'],
    ['name' => 'is_required', 'type' => 'boolean', 'description' => 'Whether the life event refuses to save without it.'],
    ['name' => 'is_sensitive', 'type' => 'boolean', 'description' => 'Whether the value is hidden until somebody asks to see it.'],
    ['name' => 'is_highlighted', 'type' => 'boolean', 'description' => 'Whether the value is repeated at the top of the life event.'],
    ['name' => 'is_unique', 'type' => 'boolean', 'description' => 'Whether two life events of this nature may hold the same value.'],
    ['name' => 'is_identifying', 'type' => 'boolean', 'description' => 'Whether two life events sharing this value are likely the same life event.'],
    ['name' => 'is_repeatable', 'type' => 'boolean', 'description' => 'Whether the field holds several values rather than one. It follows from the type.'],
    ['name' => 'repeat_min', 'type' => 'integer', 'description' => 'The fewest repetitions allowed, or null when there is no floor.'],
    ['name' => 'repeat_max', 'type' => 'integer', 'description' => 'The most repetitions allowed, or null when there is no ceiling.'],
    ['name' => 'repetition_order', 'type' => 'string', 'description' => 'How several values are ordered against each other, or null when the field holds one.'],
    ['name' => 'options', 'type' => 'object', 'description' => 'Settings belonging to this kind of field alone, or null. What it holds depends on the type.'],
    ['name' => 'validation', 'type' => 'object', 'description' => 'Extra rules a value has to satisfy, or null.'],
    ['name' => 'default_value', 'type' => 'object', 'description' => 'What the field starts filled with, or null.'],
    ['name' => 'relation_target', 'type' => 'string', 'description' => 'What a relation field points at, or null when the field is not a relation.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the field was added, as a count of seconds since 1970.'],
    ['name' => 'updated_at', 'type' => 'timestamp', 'description' => 'When it last changed, as a count of seconds, or null.'],
];

$groupAttributes = [
    ['name' => 'position', 'type' => 'integer', 'description' => 'Where the group sits in the layout, counting from 1. The fields nobody grouped are a group too, and they come first.'],
    ['name' => 'title', 'type' => 'string', 'description' => 'What the group is headed. The fields nobody grouped answer with a heading that is not drawn.'],
    ['name' => 'title_override', 'type' => 'string', 'description' => 'The heading chosen for this group alone, or null when the underlying name is used.'],
    ['name' => 'section_id', 'type' => 'string', 'description' => 'The id of the reusable block behind the group. It belongs to the engine that stores the layout and a caller rarely needs it.'],
    ['name' => 'section_name', 'type' => 'string', 'description' => 'What that block is called in the library it lives in.'],
    ['name' => 'section_version_number', 'type' => 'integer', 'description' => 'Which frozen version of the block is placed here. This is what stops a change reaching a life event already recorded.'],
    ['name' => 'section_version_status', 'type' => 'string', 'description' => 'One of draft, published or retired, for the block rather than for the nature.'],
    ['name' => 'is_collapsed_by_default', 'type' => 'boolean', 'description' => 'Whether the group starts folded shut when somebody opens the life event.'],
];

$layoutFields = [
    ...$nested('data', 'structure_version_section', 'group', 'groups', [
        ...$groupAttributes,
        ['name' => 'fields', 'type' => 'array', 'description' => 'What a life event of this nature is asked for inside this group, in the order it is shown.'],
    ], 'The groups the layout is made of, in the order they are shown. An empty list when the nature asks for nothing.'),
    ...$nested('data[].attributes.fields', 'field', 'field', 'fields', $fieldAttributes, 'What the group collects, in the order it is shown.'),
];

$exampleLayout = [
    [
        'type' => 'structure_version_section',
        'id' => '41',
        'attributes' => [
            'position' => 1,
            'title' => 'Where',
            'title_override' => 'Where',
            'section_id' => 'ec0a7b19-5d38-4c72-9a61-2f80d4b63e57',
            'section_name' => 'Where',
            'section_version_number' => 1,
            'section_version_status' => 'published',
            'is_collapsed_by_default' => false,
            'fields' => [
                [
                    'type' => 'field',
                    'id' => '88',
                    'attributes' => [
                        'key' => 'venue',
                        'type' => 'text',
                        'label' => 'Venue',
                        'help_text' => null,
                        'placeholder' => null,
                        'position' => 1,
                        'is_required' => true,
                        'is_sensitive' => false,
                        'is_highlighted' => false,
                        'is_unique' => false,
                        'is_identifying' => false,
                        'is_repeatable' => false,
                        'repeat_min' => null,
                        'repeat_max' => null,
                        'repetition_order' => null,
                        'options' => null,
                        'validation' => null,
                        'default_value' => null,
                        'relation_target' => null,
                        'created_at' => 1756339200,
                        'updated_at' => 1756339200,
                    ],
                ],
            ],
        ],
    ],
];

return [
    'name' => 'Life event types',
    'description' => 'The natures a life event may be recorded as, and the custom fields each one asks for.',
    'sections' => [
        [
            'id' => 'life-event-types-index',
            'returnFields' => ApiDocsFields::page('life_event_type', 'nature', 'natures', $typeAttributes),
            'title' => 'List life event types',
            'label' => 'List life event types',
            'method' => 'GET',
            'path' => '/life-event-types',
            'description' => 'List every nature your account can record a life event as, grouped the way the picker groups them.',
            'body' => [
                'A nature is what a life event was: lunch, a phone call, a medical appointment, a concert. It carries a name, a sentence saying what it covers, a glyph, a tint, and the custom fields a life event of that nature asks for, which are read at their own address rather than here.',
                'The list runs through the categories in the order you arranged them, and through the natures inside each category in the order you arranged those. Retired natures are listed with the rest, because one that is off is meant to be found again and switched back on.',
                'A nature the product supplied reads in the language you ask for. Once somebody renames it, their words stand in every language.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'queryParams' => $pageParams,
            'returns' => 'A page of life event type objects, and how many there are in all.',
            'response' => [
                'data' => [$type, $otherType],
                'meta' => ['page' => 1, 'per_page' => 25, 'total' => 2],
            ],
        ],
        [
            'id' => 'life-event-types-show',
            'returnFields' => ApiDocsFields::one('life_event_type', 'nature', $typeAttributes),
            'title' => 'Get a life event type',
            'label' => 'Get a life event type',
            'method' => 'GET',
            'path' => '/life-event-types/{lifeEventType}',
            'examplePath' => '/life-event-types/8b2e5c70-1a94-4f38-b6d2-0e7c94a35f18',
            'description' => 'Retrieve one nature: what it is called, what it covers, how it is drawn and where it sits.',
            'body' => [
                'The custom fields the nature asks for are not in here. They are read at their own endpoint, so listing a vocabulary does not make you carry every layout it holds.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $identifier,
            'returns' => 'A life event type object. A nature belonging to somebody else answers 404, which is also what a nature that never existed answers, and what a deleted one answers.',
            'response' => ['data' => $otherType],
        ],
        [
            'id' => 'life-event-types-create',
            'returnFields' => ApiDocsFields::one('life_event_type', 'nature', $typeAttributes),
            'title' => 'Add a life event type',
            'label' => 'Add a life event type',
            'method' => 'POST',
            'path' => '/life-event-types',
            'description' => 'Add a nature of your own, when the ones the product supplies do not describe what you actually do.',
            'body' => [
                'The nature goes at the end of the category you file it under and is offered straight away. Retire it afterwards if you are preparing a vocabulary you do not want offered yet.',
                'It starts with no custom fields, which is a perfectly ordinary state: a life event of a brand new nature can be recorded immediately, carrying nothing beyond what every life event carries. Give it fields at their own address when you want them.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'bodyParams' => [
                ['name' => 'life_event_category_id', 'type' => 'uuid', 'required' => true, 'description' => 'The category to file the nature under. A category belonging to somebody else answers 404.', 'example' => '5f8b3c21-9d07-4a64-8e12-7b0a4d6c3e95'],
                ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => 'What the nature is called. Up to 255 characters. Markup and surrounding blank space are stripped, and a name left with nothing in it is refused.', 'example' => 'Board game night'],
                ['name' => 'description', 'type' => 'string', 'required' => false, 'description' => 'A sentence saying what the nature covers. Up to 255 characters, stripped the same way.', 'example' => 'An evening around a table with a box of cardboard.'],
                ['name' => 'icon', 'type' => 'string', 'required' => false, 'description' => 'The glyph drawn beside the nature, from the catalogue the product ships. Anything else is refused.', 'example' => 'activity'],
                ['name' => 'colour', 'type' => 'string', 'required' => false, 'description' => 'The tint the nature is drawn in, from the palette the product ships. Anything else is refused.', 'example' => 'bg-sky-500'],
            ],
            'returns' => 'The life event type object that was added.',
            'responseStatus' => 201,
            'response' => ['data' => $otherType],
        ],
        [
            'id' => 'life-event-types-update',
            'returnFields' => ApiDocsFields::one('life_event_type', 'nature', $typeAttributes),
            'title' => 'Change a life event type',
            'label' => 'Change a life event type',
            'method' => 'PUT',
            'path' => '/life-event-types/{lifeEventType}',
            'examplePath' => '/life-event-types/8b2e5c70-1a94-4f38-b6d2-0e7c94a35f18',
            'description' => 'Change what a nature is called, what it covers, how it is drawn and which category it is filed under.',
            'body' => [
                'A nature the product supplied is changed like any other. is_system stops a nature being deleted and stops nothing else, so a household that calls a phone call something else writes it here.',
                'Moving a nature to another category puts it last there, because the order inside a category is an order somebody chose and an arrival has no claim on a place in it.',
                'Nothing a life event has recorded changes, since a life event names its nature rather than copying it. Renaming a nature renames it on every life event already recorded with it, which is the point.',
                'Once you write your own words they stand in every language. The wording the product supplied reads in the language of whoever is looking, and renaming gives that up.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $identifier,
            'bodyParams' => [
                ['name' => 'life_event_category_id', 'type' => 'uuid', 'required' => false, 'description' => 'The category to file the nature under. A category belonging to somebody else answers 404. It cannot be null.', 'example' => '5f8b3c21-9d07-4a64-8e12-7b0a4d6c3e95'],
                ['name' => 'name', 'type' => 'string', 'required' => false, 'description' => 'What the nature is called. Up to 255 characters. Markup and surrounding blank space are stripped, and a name left with nothing in it is refused. It cannot be null.', 'example' => 'Board game night'],
                ['name' => 'description', 'type' => 'string', 'required' => false, 'description' => 'A sentence saying what the nature covers. Up to 255 characters, stripped the same way. Null clears it.', 'example' => 'An evening around a table with a box of cardboard.'],
                ['name' => 'icon', 'type' => 'string', 'required' => false, 'description' => 'The glyph drawn beside the nature, from the catalogue the product ships. Anything else is refused.', 'example' => 'activity'],
                ['name' => 'colour', 'type' => 'string', 'required' => false, 'description' => 'The tint the nature is drawn in, from the palette the product ships. Anything else is refused.', 'example' => 'bg-sky-500'],
            ],
            'returns' => 'The life event type object as it now reads.',
            'response' => ['data' => $otherType],
        ],
        [
            'id' => 'life-event-types-destroy',
            'title' => 'Delete a life event type',
            'label' => 'Delete a life event type',
            'method' => 'DELETE',
            'path' => '/life-event-types/{lifeEventType}',
            'examplePath' => '/life-event-types/8b2e5c70-1a94-4f38-b6d2-0e7c94a35f18',
            'description' => 'Remove a nature you added.',
            'body' => [
                'A nature the product supplied stays, because the wording it falls back to belongs to the product. Retire it instead: it leaves the choice offered when a life event is recorded, and everything already written keeps reading as it did.',
                'A deletion is recoverable, so an accident is not the end of anything. The custom fields the nature asked for are deliberately left where they are, so a restored nature comes back asking for what it asked for.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $identifier,
            'returns' => 'Nothing. A nature the product supplied answers 422 naming that reason, and one belonging to somebody else answers 404.',
            'responseStatus' => 204,
            'response' => [],
        ],
        [
            'id' => 'life-event-types-activation',
            'returnFields' => ApiDocsFields::one('life_event_type', 'nature', $typeAttributes),
            'title' => 'Offer or retire a life event type',
            'label' => 'Offer or retire a life event type',
            'method' => 'PUT',
            'path' => '/life-event-types/{lifeEventType}/activation',
            'examplePath' => '/life-event-types/8b2e5c70-1a94-4f38-b6d2-0e7c94a35f18/activation',
            'description' => 'Say whether a nature may still be chosen when a life event is recorded.',
            'body' => [
                'Retiring a nature takes it out of the choice offered when somebody records a life event. It is the answer wherever deletion is refused, and it works on a nature the product supplied as readily as on your own.',
                'Nothing already written is touched. Every life event keeps its nature, its drawing and its custom fields, and the nature keeps the place it had, so offering it again puts it back where it was rather than at the end.',
                'Send the state you want rather than asking for a toggle, so sending the same value twice confirms it instead of inverting it.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $identifier,
            'bodyParams' => [
                ['name' => 'is_active', 'type' => 'boolean', 'required' => true, 'description' => 'True to offer the nature again, false to retire it.', 'example' => 'false'],
            ],
            'returns' => 'The life event type object in the state you asked for.',
            'response' => ['data' => $otherType],
        ],
        [
            'id' => 'life-event-types-position',
            'returnFields' => ApiDocsFields::one('life_event_type', 'nature', $typeAttributes),
            'title' => 'Move a life event type',
            'label' => 'Move a life event type',
            'method' => 'PUT',
            'path' => '/life-event-types/{lifeEventType}/position',
            'examplePath' => '/life-event-types/8b2e5c70-1a94-4f38-b6d2-0e7c94a35f18/position',
            'description' => 'Put a nature somewhere else inside the category it is filed under, so the ones you reach for sit at the top.',
            'body' => [
                'Every nature of that one category is numbered from 1 again once the move is made, retired ones counted among them. A position past the end puts the nature last rather than being refused.',
                'Moving a nature to another category is not this: it is an ordinary change, and it puts the nature last where it arrives.',
                'A nature the product supplied may be moved like any other. Where a nature sits changes the order it is read in and changes nothing else.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $identifier,
            'bodyParams' => [
                ['name' => 'position', 'type' => 'integer', 'required' => true, 'description' => 'Where the nature should sit inside its category, counting from 1.', 'example' => '1'],
            ],
            'returns' => 'The life event type object where you put it.',
            'response' => ['data' => $otherType],
        ],
        [
            'id' => 'life-event-types-fields',
            'returnFields' => $layoutFields,
            'title' => 'Read the custom fields of a life event type',
            'label' => 'Read the custom fields of a life event type',
            'method' => 'GET',
            'path' => '/life-event-types/{lifeEventType}/fields',
            'examplePath' => '/life-event-types/8b2e5c70-1a94-4f38-b6d2-0e7c94a35f18/fields',
            'description' => 'Read what a life event of one nature is asked for: the groups, and the fields in each.',
            'body' => [
                'A group is a heading drawn above its fields. The fields nobody grouped are a group too, placed first with its heading not drawn, because a field belongs to a group and to nothing else.',
                'A nature nobody has given fields to answers an empty list rather than 404. That is the state every nature starts in.',
                'Each field carries the key it is known by. Send that key back when you write the layout and the field is kept rather than replaced, which is what stops the values already recorded against it being orphaned.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $identifier,
            'returns' => 'The groups of the layout, each with its fields. A nature belonging to somebody else answers 404.',
            'response' => ['data' => $exampleLayout],
        ],
        [
            'id' => 'life-event-types-fields-update',
            'returnFields' => $layoutFields,
            'title' => 'Write the custom fields of a life event type',
            'label' => 'Write the custom fields of a life event type',
            'method' => 'PUT',
            'path' => '/life-event-types/{lifeEventType}/fields',
            'examplePath' => '/life-event-types/8b2e5c70-1a94-4f38-b6d2-0e7c94a35f18/fields',
            'description' => 'Send the layout a life event of this nature should ask for.',
            'body' => [
                'This takes the finished layout rather than one change at a time, because that is the one gesture the product has for it. The layout is two lists, groups and fields, each sent whole. A list left out of the body keeps what is stored. A list sent replaces what is stored, so anything left out of it is gone.',
                'A field is matched to the one already there by its key. Send a field back with its key to keep it, leave the key out to add a field, and leave a field out altogether to take it away. Changing what kind of field it is, or what a relation points at, writes the field again in place under the same key.',
                'A group is matched by the placement_id you read it with. Leave that out to add a group, and leave a group out to take it away, which files its fields under no group rather than deleting them.',
                'Every save publishes a new frozen version of the layout beside the old one. A life event already recorded names the version it was written on and never follows, so it keeps the fields and the values it was written with. This is the rule that makes the layout safe to change.',
                'A kind of field the product does not offer, and a group without a name, are both refused, and the layout already there is left exactly as it was.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $identifier,
            'bodyParams' => [
                ['name' => 'groups', 'type' => 'array', 'required' => false, 'description' => 'The groups of the layout, in the order you want them drawn.'],
                ['name' => 'groups[].placement_id', 'type' => 'integer', 'required' => false, 'description' => 'The id you read the group with, to keep it rather than replace it. Leave it out for a new group.', 'example' => '41'],
                ['name' => 'groups[].name', 'type' => 'string', 'required' => true, 'description' => 'What the group is headed. Up to 255 characters, and a group without one is refused.', 'example' => 'Where'],
                ['name' => 'groups[].fields', 'type' => 'array', 'required' => false, 'description' => 'The fields in that group, in the order you want them asked for. Each is shaped like an entry of the top level fields list.'],
                ['name' => 'fields', 'type' => 'array', 'required' => false, 'description' => 'The fields that belong to no group. They are asked for first, under no heading.'],
                ['name' => 'fields[].key', 'type' => 'string', 'required' => false, 'description' => 'The key you read the field with, to keep it rather than replace it. Leave it out for a new field. Up to 64 characters.', 'example' => 'venue'],
                ['name' => 'fields[].label', 'type' => 'string', 'required' => true, 'description' => 'What the field is called on the form. Up to 255 characters.', 'example' => 'Venue'],
                ['name' => 'fields[].type', 'type' => 'string', 'required' => true, 'description' => 'What kind of thing the field holds, from the list the read answers with. Anything else is refused.', 'example' => 'text'],
                ['name' => 'fields[].is_required', 'type' => 'boolean', 'required' => false, 'description' => 'Whether the life event refuses to save without it.', 'example' => 'true'],
                ['name' => 'fields[].help_text', 'type' => 'string', 'required' => false, 'description' => 'A sentence shown under the field. Up to 255 characters.', 'example' => 'The place it happened.'],
                ['name' => 'fields[].settings', 'type' => 'object', 'required' => false, 'description' => 'Settings belonging to that kind of field alone, such as max_length on a text field or decimals on a number.'],
                ['name' => 'fields[].choices', 'type' => 'array', 'required' => false, 'description' => 'What a select, multiselect or radio field offers, in order. Blank entries are dropped.'],
            ],
            'returns' => 'The layout as it now stands, read back exactly as the read answers it.',
            'response' => ['data' => $exampleLayout],
        ],
    ],
];
