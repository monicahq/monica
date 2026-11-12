<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();

$category = [
    'type' => 'life_event_category',
    'id' => '5f8b3c21-9d07-4a64-8e12-7b0a4d6c3e95',
    'attributes' => [
        'name' => 'Relationships and family',
        'position' => 1,
        'is_system' => true,
        'is_active' => true,
        'life_event_type_count' => 0,
        'created_at' => 1756252800,
        'updated_at' => 1756252800,
    ],
    'links' => [
        'self' => $base.'/life-event-categories/5f8b3c21-9d07-4a64-8e12-7b0a4d6c3e95',
    ],
];

$otherCategory = [
    'type' => 'life_event_category',
    'id' => 'a71d6e04-2b58-4c39-9f80-3e5c1a7b2d46',
    'attributes' => [
        'name' => 'Volunteering',
        'position' => 9,
        'is_system' => false,
        'is_active' => true,
        'life_event_type_count' => 0,
        'created_at' => 1756339200,
        'updated_at' => 1756425600,
    ],
    'links' => [
        'self' => $base.'/life-event-categories/a71d6e04-2b58-4c39-9f80-3e5c1a7b2d46',
    ],
];

$typeOfCategory = [
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

$pageParams = [
    ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Which page to answer with.', 'default' => '1'],
    ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'How many categories one page holds, between 1 and 100. A number outside the range is brought back into it rather than refused.', 'default' => '25'],
];

$identifier = [
    ['name' => 'lifeEventCategory', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of the category, which is the id it comes back with. A path that is not a uuid answers 404 without a query being run.'],
];

$categoryAttributes = [
    ['name' => 'name', 'type' => 'string', 'description' => 'What the group is called. A category the product supplied reads in the language you ask for, until somebody renames it, after which their words stand in every language. Up to 255 characters, reduced to plain text, so no markup survives here.'],
    ['name' => 'position', 'type' => 'integer', 'description' => 'Where the category sits in the settings, the pickers and the journal filters, counting from 1. Retired categories are numbered along with the rest, so one switched back on finds the place it had.'],
    ['name' => 'is_system', 'type' => 'boolean', 'description' => 'Whether the product supplied the category with the account. It governs one thing and one thing only: a supplied category cannot be deleted. It may be renamed, moved and retired like any other.'],
    ['name' => 'is_active', 'type' => 'boolean', 'description' => 'Whether the category and the natures under it are offered when a life event is recorded. False removes the whole group from that choice and touches nothing already written.'],
    ['name' => 'life_event_type_count', 'type' => 'integer', 'description' => 'How many life event natures are filed under the category, retired ones counted. It is what decides whether the category may be deleted.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the category was added, as a count of seconds since 1970 rather than a written date.'],
    ['name' => 'updated_at', 'type' => 'timestamp', 'description' => 'When it last changed, as a count of seconds. Equal to created_at until it first changes.'],
];

return [
    'name' => 'Life event categories',
    'description' => 'The groups life event natures are filed under, which order the vocabulary and give statistics a level to count at.',
    'sections' => [
        [
            'id' => 'life-event-categories-index',
            'returnFields' => ApiDocsFields::page('life_event_category', 'category', 'categories', $categoryAttributes),
            'title' => 'List life event categories',
            'label' => 'List life event categories',
            'method' => 'GET',
            'path' => '/life-event-categories',
            'description' => 'List the groups your account files its life event natures under, in the order you arranged them.',
            'body' => [
                'A category groups the vocabulary in settings and pickers, filters the journal and gives aggregate statistics a level to count at. It never decides which fields a life event carries, who participated, or how it is shared. Moving a nature between groups changes how its life events are grouped, including past ones, and changes no recorded fact.',
                'Every account starts with eight: Relationships and family, Work and education, Health and wellbeing, Home and daily life, Leisure and culture, Travel and experiences, Personal and Other. Other is the fallback, so a nature is never left unclassified. All eight are marked with is_system, which stops them being deleted and nothing else.',
                'Retired categories are listed with the rest, because one that is off is meant to be found again and switched back on.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'queryParams' => $pageParams,
            'returns' => 'A page of life event category objects, and how many there are in all.',
            'response' => [
                'data' => [$category, $otherCategory],
                'meta' => ['page' => 1, 'per_page' => 25, 'total' => 2],
            ],
        ],
        [
            'id' => 'life-event-categories-show',
            'returnFields' => ApiDocsFields::one('life_event_category', 'category', $categoryAttributes),
            'title' => 'Get a life event category',
            'label' => 'Get a life event category',
            'method' => 'GET',
            'path' => '/life-event-categories/{lifeEventCategory}',
            'examplePath' => '/life-event-categories/5f8b3c21-9d07-4a64-8e12-7b0a4d6c3e95',
            'description' => 'Retrieve one group: what it is called, where it sits, and how many natures it holds.',
            'body' => [
                'The natures themselves are not in here, only how many there are. Ask for the types of the category to read them.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $identifier,
            'returns' => 'A life event category object. A category belonging to somebody else answers 404, which is also what a category that never existed answers, and what a deleted one answers.',
            'response' => ['data' => $category],
        ],
        [
            'id' => 'life-event-categories-types',
            'returnFields' => ApiDocsFields::page('life_event_type', 'nature', 'natures', [
                ['name' => 'life_event_category_id', 'type' => 'uuid', 'description' => 'The group the nature is filed under, which is the category you asked about.'],
                ['name' => 'name', 'type' => 'string', 'description' => 'What the nature is called, in the language you ask for while the product still supplies the wording.'],
                ['name' => 'description', 'type' => 'string', 'description' => 'A sentence saying what the nature covers, or null when nobody wrote one.'],
                ['name' => 'icon', 'type' => 'string', 'description' => 'The glyph drawn beside the nature, or null.'],
                ['name' => 'colour', 'type' => 'string', 'description' => 'The tint the nature is drawn in, or null.'],
                ['name' => 'position', 'type' => 'integer', 'description' => 'Where the nature sits inside this group, counting from 1.'],
                ['name' => 'is_system', 'type' => 'boolean', 'description' => 'Whether the product supplied the nature, which governs deletion and nothing else.'],
                ['name' => 'is_active', 'type' => 'boolean', 'description' => 'Whether the nature is offered when a life event is recorded.'],
                ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the nature was added, as a count of seconds since 1970.'],
                ['name' => 'updated_at', 'type' => 'timestamp', 'description' => 'When it last changed, as a count of seconds, or null.'],
            ]),
            'title' => 'List the life event types of a category',
            'label' => 'List the life event types of a category',
            'method' => 'GET',
            'path' => '/life-event-categories/{lifeEventCategory}/types',
            'examplePath' => '/life-event-categories/5f8b3c21-9d07-4a64-8e12-7b0a4d6c3e95/types',
            'description' => 'List the natures filed under one group, in the order you arranged them.',
            'body' => [
                'This is the grouped reading the settings screen shows, one group at a time. Retired natures are listed with the rest, and so are the natures of a retired category, because the list says what is filed where rather than what is currently offered.',
                'The natures are described exactly as the life event types endpoints describe them, so anything you read here can be changed there without asking for it again.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $identifier,
            'queryParams' => $pageParams,
            'returns' => 'A page of life event type objects, and how many there are in all. A category belonging to somebody else answers 404 rather than an empty list.',
            'response' => [
                'data' => [$typeOfCategory],
                'meta' => ['page' => 1, 'per_page' => 25, 'total' => 1],
            ],
        ],
        [
            'id' => 'life-event-categories-create',
            'returnFields' => ApiDocsFields::one('life_event_category', 'category', $categoryAttributes),
            'title' => 'Add a life event category',
            'label' => 'Add a life event category',
            'method' => 'POST',
            'path' => '/life-event-categories',
            'description' => 'Add a group of your own, when the eight the product supplies do not sort your vocabulary the way you think of it.',
            'body' => [
                'The category goes at the end of the list and is offered straight away. It holds nothing until natures are filed under it, and a group holding nothing is a perfectly ordinary state: Other is supplied that way.',
                'A category does no work beyond grouping, filtering and counting. Adding one called Health changes no life event behaviour. Contact history is determined by participants, not by categories or natures.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'bodyParams' => [
                ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => 'What the group is called. Up to 255 characters. Markup and surrounding blank space are stripped, and a name left with nothing in it is refused.', 'example' => 'Volunteering'],
            ],
            'returns' => 'The life event category object that was added.',
            'responseStatus' => 201,
            'response' => ['data' => $otherCategory],
        ],
        [
            'id' => 'life-event-categories-update',
            'returnFields' => ApiDocsFields::one('life_event_category', 'category', $categoryAttributes),
            'title' => 'Rename a life event category',
            'label' => 'Rename a life event category',
            'method' => 'PUT',
            'path' => '/life-event-categories/{lifeEventCategory}',
            'examplePath' => '/life-event-categories/a71d6e04-2b58-4c39-9f80-3e5c1a7b2d46',
            'description' => 'Change what a group is called, whether the product supplied it or you added it.',
            'body' => [
                'A category the product supplied is renamed like any other. is_system stops a category being deleted and stops nothing else, so a French household preferring Santé to Health and wellbeing writes it here.',
                'What the product does with the category is untouched: its stable identity, its place in the order and whether it is offered all stay, and nothing filed under it moves.',
                'Once you write your own words they stand in every language. The wording the product supplied reads in the language of whoever is looking, and renaming gives that up, which is the bargain every renameable supplied record makes here.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $identifier,
            'bodyParams' => [
                ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => 'What the group is called. Up to 255 characters.', 'example' => 'Volunteering'],
            ],
            'returns' => 'The life event category object as it now reads.',
            'response' => ['data' => $otherCategory],
        ],
        [
            'id' => 'life-event-categories-destroy',
            'title' => 'Delete a life event category',
            'label' => 'Delete a life event category',
            'method' => 'DELETE',
            'path' => '/life-event-categories/{lifeEventCategory}',
            'examplePath' => '/life-event-categories/a71d6e04-2b58-4c39-9f80-3e5c1a7b2d46',
            'description' => 'Remove a group you added that holds nothing.',
            'body' => [
                'Two categories cannot go. One the product supplied stays, because the wording it falls back to belongs to the product. One that still holds natures stays, and the answer says how many, because removing it would take them with it. Move them elsewhere first, or retire the category instead.',
                'Both refusals bite today, since natures are filed under categories from the moment an account is created.',
                'A deletion is recoverable, so an accident is not the end of anything.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $identifier,
            'returns' => 'Nothing. A supplied category, and one still holding a nature, each answer 422 naming that reason, and one belonging to somebody else answers 404.',
            'responseStatus' => 204,
            'response' => [],
        ],
        [
            'id' => 'life-event-categories-activation',
            'returnFields' => ApiDocsFields::one('life_event_category', 'category', $categoryAttributes),
            'title' => 'Offer or retire a life event category',
            'label' => 'Offer or retire a life event category',
            'method' => 'PUT',
            'path' => '/life-event-categories/{lifeEventCategory}/activation',
            'examplePath' => '/life-event-categories/a71d6e04-2b58-4c39-9f80-3e5c1a7b2d46/activation',
            'description' => 'Say whether a group, and every nature under it, may still be chosen when a life event is recorded.',
            'body' => [
                'Retiring a category takes the whole group out of the choice offered when somebody records a life event. It is the answer wherever deletion is refused, and it works on a category the product supplied as readily as on your own.',
                'Nothing already written is touched. Every life event keeps its nature and reads as it did, and the category keeps the place it had, so offering it again puts it back where it was rather than at the end.',
                'Retiring a group retires the choice of every nature under it, and offering it again brings them all back, each still holding whether it is offered in its own right.',
                'Send the state you want rather than asking for a toggle, so sending the same value twice confirms it instead of inverting it.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $identifier,
            'bodyParams' => [
                ['name' => 'is_active', 'type' => 'boolean', 'required' => true, 'description' => 'True to offer the category again, false to retire it.', 'example' => 'false'],
            ],
            'returns' => 'The life event category object in the state you asked for.',
            'response' => ['data' => $category],
        ],
        [
            'id' => 'life-event-categories-position',
            'returnFields' => ApiDocsFields::one('life_event_category', 'category', $categoryAttributes),
            'title' => 'Move a life event category',
            'label' => 'Move a life event category',
            'method' => 'PUT',
            'path' => '/life-event-categories/{lifeEventCategory}/position',
            'examplePath' => '/life-event-categories/a71d6e04-2b58-4c39-9f80-3e5c1a7b2d46/position',
            'description' => 'Put a group somewhere else in the list, so the ones you reach for sit at the top.',
            'body' => [
                'Every category of your account is numbered from 1 again once the move is made, retired ones counted among them. A position past the end puts the category last rather than being refused.',
                'A category the product supplied may be moved like any other. Where a group sits changes the order it is read in, in the settings, the pickers and the journal filters, and changes nothing else.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $identifier,
            'bodyParams' => [
                ['name' => 'position', 'type' => 'integer', 'required' => true, 'description' => 'Where the category should sit, counting from 1.', 'example' => '1'],
            ],
            'returns' => 'The life event category object in its new place.',
            'response' => ['data' => $category],
        ],
    ],
];
