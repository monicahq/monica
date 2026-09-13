<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();

$type = [
    'type' => 'relationship_type',
    'id' => '3a9c5e21-7b40-4f68-8d13-6e2f0a4b9c57',
    'attributes' => [
        'relationship_category_id' => '7d1f4a83-2c06-4e59-9b27-8f3a5d0e6c14',
        'name' => 'Parent / Child',
        'role_a' => 'Parent',
        'role_b' => 'Child',
        'is_symmetric' => false,
        'system_behavior' => 'genealogy_parentage',
        'position' => 1,
        'is_system' => true,
        'is_active' => true,
        'relationship_count' => 12,
        'created_at' => 1756252800,
        'updated_at' => null,
    ],
    'links' => [
        'self' => $base.'/relationship-types/3a9c5e21-7b40-4f68-8d13-6e2f0a4b9c57',
    ],
];

$otherType = [
    'type' => 'relationship_type',
    'id' => 'c60d8f14-9a27-4b53-8e01-2d7f5c3a6b48',
    'attributes' => [
        'relationship_category_id' => '7d1f4a83-2c06-4e59-9b27-8f3a5d0e6c14',
        'name' => 'Godparent / Godchild',
        'role_a' => 'Godparent',
        'role_b' => 'Godchild',
        'is_symmetric' => false,
        'system_behavior' => null,
        'position' => 6,
        'is_system' => false,
        'is_active' => true,
        'relationship_count' => 0,
        'created_at' => 1756339200,
        'updated_at' => 1756425600,
    ],
    'links' => [
        'self' => $base.'/relationship-types/c60d8f14-9a27-4b53-8e01-2d7f5c3a6b48',
    ],
];

$pageParams = [
    ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Which page to answer with.', 'default' => '1'],
    ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'How many types one page holds, between 1 and 100. A number outside the range is brought back into it rather than refused.', 'default' => '25'],
];

$categoryIdentifier = [
    ['name' => 'relationshipCategory', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of the category whose types you want. A category belonging to somebody else answers 404.'],
];

$identifier = [
    ['name' => 'relationshipType', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of the type, which is the id it comes back with. A path that is not a uuid answers 404 without a query being run.'],
];

$typeAttributes = [
    ['name' => 'relationship_category_id', 'type' => 'uuid', 'description' => 'The group the type is filed under. It groups the list and the picker and has no other effect: moving a type between categories never changes what the product does with it.'],
    ['name' => 'name', 'type' => 'string', 'description' => 'What the link is called: Parent / Child, Sibling, Colleague. A type the product supplied reads in the language you ask for, until somebody rewrites it, after which their words stand in every language. Up to 255 characters, reduced to plain text.'],
    ['name' => 'role_a', 'type' => 'string', 'description' => 'How the first person relates to the second. In a Parent / Child link the first person is the Parent. It is read in the same way the name is.'],
    ['name' => 'role_b', 'type' => 'string', 'description' => 'How the second person relates to the first. A symmetric type carries the same word here as in role_a, and rewording the two never swaps the people over: the first role stays on the first person of every relationship already written.'],
    ['name' => 'is_symmetric', 'type' => 'boolean', 'description' => 'Whether both people read the same word, as Friend does, rather than a different one on each side, as Parent / Child does. It is frozen once the product supplied the type or a relationship uses it, because turning it round would rewrite what people wrote years ago.'],
    ['name' => 'system_behavior', 'type' => 'string', 'description' => 'Either genealogy_parentage, which feeds the family tree, or romantic, which feeds romantic history, or null. It is attached to the supplied type itself rather than to its name or its category, so renaming one or moving it elsewhere changes the words on screen and nothing more. A type you invent never acquires one.'],
    ['name' => 'position', 'type' => 'integer', 'description' => 'Where the type sits inside its category, counting from 1. Retired types are numbered along with the rest.'],
    ['name' => 'is_system', 'type' => 'boolean', 'description' => 'Whether the product supplied the type with the account. A supplied type may be reworded, moved and retired, never deleted, and its direction never changes.'],
    ['name' => 'is_active', 'type' => 'boolean', 'description' => 'Whether the type is offered when a new relationship is written. False stops it being chosen again and touches nothing already written.'],
    ['name' => 'relationship_count', 'type' => 'integer', 'description' => 'How many relationships are written with this type, across every vault of your account. A relationship in the bin is not counted. It decides both whether the type may be deleted and whether its direction may still move.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the type was added, as a count of seconds since 1970 rather than a written date.'],
    ['name' => 'updated_at', 'type' => 'timestamp', 'description' => 'When it last changed, as a count of seconds, or null if it has not changed since it was added.'],
];

return [
    'name' => 'Relationship types',
    'sections' => [
        [
            'id' => 'relationship-types-index',
            'returnFields' => ApiDocsFields::page('relationship_type', 'type', 'types', $typeAttributes),
            'title' => 'List relationship types',
            'label' => 'List relationship types',
            'method' => 'GET',
            'path' => '/relationship-types',
            'description' => 'List every kind of link your account can put between two people, grouped order first.',
            'body' => [
                'A relationship type is the vocabulary your account uses about the people in it: Parent / Child, Sibling, Partner, Friend, Colleague. Every account starts with ten of them under four of the five supplied categories.',
                'The order is the one the picker offers: the category position first, then the position inside the category. Retired types are listed with the rest, because one that is off is meant to be found again.',
                'To read one category at a time instead, ask for the types of that category.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'queryParams' => $pageParams,
            'returns' => 'A page of relationship type objects, and how many there are in all.',
            'response' => [
                'data' => [$type, $otherType],
                'meta' => ['page' => 1, 'per_page' => 25, 'total' => 2],
            ],
        ],
        [
            'id' => 'relationship-types-by-category',
            'returnFields' => ApiDocsFields::page('relationship_type', 'type', 'types', $typeAttributes),
            'title' => 'List the types of one category',
            'label' => 'List the types of one category',
            'method' => 'GET',
            'path' => '/relationship-categories/{relationshipCategory}/types',
            'examplePath' => '/relationship-categories/7d1f4a83-2c06-4e59-9b27-8f3a5d0e6c14/types',
            'description' => 'List the relationship types filed under one group, in the order you arranged them.',
            'body' => [
                'This is the grouped reading the settings screen shows, one group at a time. The objects are relationship types, described in full in the relationship types reference.',
                'A category holding nothing answers an empty list rather than an error, which is the state Other is supplied in.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => $categoryIdentifier,
            'queryParams' => $pageParams,
            'returns' => 'A page of relationship type objects, and how many the category holds in all.',
            'response' => [
                'data' => [$type],
                'meta' => ['page' => 1, 'per_page' => 25, 'total' => 1],
            ],
        ],
        [
            'id' => 'relationship-types-show',
            'returnFields' => ApiDocsFields::one('relationship_type', 'type', $typeAttributes),
            'title' => 'Get a relationship type',
            'label' => 'Get a relationship type',
            'method' => 'GET',
            'path' => '/relationship-types/{relationshipType}',
            'examplePath' => '/relationship-types/3a9c5e21-7b40-4f68-8d13-6e2f0a4b9c57',
            'description' => 'Retrieve one kind of link: how it reads from each side, and how many relationships use it.',
            'body' => [
                'The relationships written with the type are not in here. This is the vocabulary rather than what was said with it.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => $identifier,
            'returns' => 'A relationship type object. A type belonging to somebody else answers 404, which is also what a type that never existed answers, and what a deleted one answers.',
            'response' => ['data' => $type],
        ],
        [
            'id' => 'relationship-types-create',
            'returnFields' => ApiDocsFields::one('relationship_type', 'type', $typeAttributes),
            'title' => 'Add a relationship type',
            'label' => 'Add a relationship type',
            'method' => 'POST',
            'path' => '/relationship-types',
            'description' => 'Add a kind of link the product did not supply, when the ten it ships with fall short.',
            'body' => [
                'Say whether both people read the same word or a different one on each side. A symmetric type, Friend for instance, takes one role and reads with it from both sides, so role_b may be left out. A directed type, Godparent / Godchild for instance, takes a role for each side, and the first person always carries role_a.',
                'The type goes at the end of the category you file it under, and is offered straight away unless you say otherwise. It never acquires a system_behavior: the family tree and romantic history read the types the product supplied, whatever you call yours.',
                'A name another type of your account already carries is refused, so the picker never offers the same word twice.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'bodyParams' => [
                ['name' => 'relationship_category_id', 'type' => 'uuid', 'required' => true, 'description' => 'The group to file the type under. A category belonging to somebody else is refused.', 'example' => '7d1f4a83-2c06-4e59-9b27-8f3a5d0e6c14'],
                ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => 'What the link is called. Up to 255 characters. Markup and surrounding blank space are stripped, and a name left with nothing in it is refused.', 'example' => 'Godparent / Godchild'],
                ['name' => 'role_a', 'type' => 'string', 'required' => true, 'description' => 'How the first person relates to the second. Up to 255 characters.', 'example' => 'Godparent'],
                ['name' => 'role_b', 'type' => 'string', 'required' => false, 'description' => 'How the second person relates to the first. Required when is_symmetric is false; ignored and set to role_a when it is true.', 'example' => 'Godchild'],
                ['name' => 'is_symmetric', 'type' => 'boolean', 'required' => false, 'description' => 'Whether both people read the same word. Defaults to true.', 'example' => 'false'],
                ['name' => 'is_active', 'type' => 'boolean', 'required' => false, 'description' => 'Whether the type is offered for a new relationship. Defaults to true.', 'example' => 'true'],
            ],
            'returns' => 'The relationship type object that was added.',
            'responseStatus' => 201,
            'response' => ['data' => $otherType],
        ],
        [
            'id' => 'relationship-types-update',
            'returnFields' => ApiDocsFields::one('relationship_type', 'type', $typeAttributes),
            'title' => 'Reword a relationship type',
            'label' => 'Reword a relationship type',
            'method' => 'PUT',
            'path' => '/relationship-types/{relationshipType}',
            'examplePath' => '/relationship-types/c60d8f14-9a27-4b53-8e01-2d7f5c3a6b48',
            'description' => 'Change what a type is called, how it reads from each side, and which group it sits in.',
            'body' => [
                'This works on a type the product supplied as readily as on one you added. A French household preferring Conjoint to Spouse writes it here, and what the product does with that type is untouched: its system_behavior, its direction and its identity all stay.',
                'Rewording the two roles of a directed type never swaps the people over. The first person keeps role_a in every relationship already written, so correcting a word cannot quietly turn parents into children.',
                'The direction stays as it is unless you send is_symmetric. Send it against a type the product supplied, or one that relationships already use, and the request is refused rather than quietly ignored, because turning a type round would rewrite what people wrote years ago.',
                'Whether the type is offered, and where it sits, are not changed here. Each has an address of its own below.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => $identifier,
            'bodyParams' => [
                ['name' => 'relationship_category_id', 'type' => 'uuid', 'required' => true, 'description' => 'The group the type sits in. Moving it between groups changes nothing about what the product does with it.', 'example' => '7d1f4a83-2c06-4e59-9b27-8f3a5d0e6c14'],
                ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => 'What the link is called. A name another type of your account carries is refused.', 'example' => 'Godparent / Godchild'],
                ['name' => 'role_a', 'type' => 'string', 'required' => true, 'description' => 'How the first person relates to the second.', 'example' => 'Godparent'],
                ['name' => 'role_b', 'type' => 'string', 'required' => false, 'description' => 'How the second person relates to the first. Required when the type is directed.', 'example' => 'Godchild'],
                ['name' => 'is_symmetric', 'type' => 'boolean', 'required' => false, 'description' => 'Send it only to change the direction. Left out, the direction holds. Refused for a supplied type and for one in use.', 'example' => 'false'],
            ],
            'returns' => 'The relationship type object as it now reads.',
            'response' => ['data' => $otherType],
        ],
        [
            'id' => 'relationship-types-destroy',
            'title' => 'Delete a relationship type',
            'label' => 'Delete a relationship type',
            'method' => 'DELETE',
            'path' => '/relationship-types/{relationshipType}',
            'examplePath' => '/relationship-types/c60d8f14-9a27-4b53-8e01-2d7f5c3a6b48',
            'description' => 'Remove a type you added and never used.',
            'body' => [
                'Two types cannot go. One the product supplied stays, because the family tree and romantic history are written against it. One that relationships already use stays, because removing it would take the words off links somebody wrote down. Both answer 422 and say which it is.',
                'Stopping the type being offered is the answer in either case: it leaves the picker, and every relationship written with it keeps reading exactly as before.',
                'A deletion is recoverable, so an accident is not the end of anything.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => $identifier,
            'returns' => 'Nothing. A type that is supplied or in use answers 422 naming that reason, and one belonging to somebody else answers 404.',
            'responseStatus' => 204,
            'response' => [],
        ],
        [
            'id' => 'relationship-types-activation',
            'returnFields' => ApiDocsFields::one('relationship_type', 'type', $typeAttributes),
            'title' => 'Offer or retire a relationship type',
            'label' => 'Offer or retire a relationship type',
            'method' => 'PUT',
            'path' => '/relationship-types/{relationshipType}/activation',
            'examplePath' => '/relationship-types/c60d8f14-9a27-4b53-8e01-2d7f5c3a6b48/activation',
            'description' => 'Say whether a type may still be chosen when a new relationship is written.',
            'body' => [
                'This is the answer wherever deletion is refused. A type the product supplied cannot go, and neither can one that relationships already use, but either can be retired, which takes it out of the choice and leaves every relationship written with it reading exactly as before.',
                'The type keeps the place it had, so offering it again puts it back where it was rather than at the end. Its wording is untouched.',
                'Send the state you want rather than asking for a toggle, so sending the same value twice confirms it instead of inverting it.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => $identifier,
            'bodyParams' => [
                ['name' => 'is_active', 'type' => 'boolean', 'required' => true, 'description' => 'True to offer the type again, false to retire it.', 'example' => 'false'],
            ],
            'returns' => 'The relationship type object in the state you asked for.',
            'response' => ['data' => $type],
        ],
        [
            'id' => 'relationship-types-position',
            'returnFields' => ApiDocsFields::one('relationship_type', 'type', $typeAttributes),
            'title' => 'Move a relationship type',
            'label' => 'Move a relationship type',
            'method' => 'PUT',
            'path' => '/relationship-types/{relationshipType}/position',
            'examplePath' => '/relationship-types/c60d8f14-9a27-4b53-8e01-2d7f5c3a6b48/position',
            'description' => 'Put a type somewhere else inside its group, so the ones you use most sit at the top of the picker.',
            'body' => [
                'A type moves inside the category it is filed under, and only the types of that category are renumbered from 1, retired ones counted among them. A position past the end puts the type last rather than being refused.',
                'To move a type into a different group, reword it and send the group you want instead. This address only changes the order.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => $identifier,
            'bodyParams' => [
                ['name' => 'position', 'type' => 'integer', 'required' => true, 'description' => 'Where the type should sit inside its category, counting from 1.', 'example' => '1'],
            ],
            'returns' => 'The relationship type object in its new place.',
            'response' => ['data' => $type],
        ],
    ],
];
