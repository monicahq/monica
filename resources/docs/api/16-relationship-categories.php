<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();

$category = [
    'type' => 'relationship_category',
    'id' => '7d1f4a83-2c06-4e59-9b27-8f3a5d0e6c14',
    'attributes' => [
        'name' => 'Family',
        'position' => 1,
        'is_system' => true,
        'is_active' => true,
        'type_count' => 5,
        'created_at' => 1756252800,
        'updated_at' => 1756252800,
    ],
    'links' => [
        'self' => $base.'/relationship-categories/7d1f4a83-2c06-4e59-9b27-8f3a5d0e6c14',
    ],
];

$otherCategory = [
    'type' => 'relationship_category',
    'id' => 'b42e9c07-5a18-4d73-8e60-1c9f2b7a4d35',
    'attributes' => [
        'name' => 'Neighbourhood',
        'position' => 6,
        'is_system' => false,
        'is_active' => true,
        'type_count' => 0,
        'created_at' => 1756339200,
        'updated_at' => 1756425600,
    ],
    'links' => [
        'self' => $base.'/relationship-categories/b42e9c07-5a18-4d73-8e60-1c9f2b7a4d35',
    ],
];

$pageParams = [
    ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Which page to answer with.', 'default' => '1'],
    ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'How many categories one page holds, between 1 and 100. A number outside the range is brought back into it rather than refused.', 'default' => '25'],
];

$identifier = [
    ['name' => 'relationshipCategory', 'type' => 'uuid', 'required' => true, 'description' => 'The id of the category. A path that is not a uuid answers 404 without a query being run.'],
];

$categoryAttributes = [
    ['name' => 'name', 'type' => 'string', 'description' => 'What the category is called. A category the product supplied reads in the language you ask for, until somebody rewrites it, after which their words stand in every language. Up to 255 characters, reduced to plain text, so no markup survives here.'],
    ['name' => 'position', 'type' => 'integer', 'description' => 'Where the category sits in the settings and in the picker, counting from 1. Retired categories are numbered along with the rest, so one switched back on finds the place it had.'],
    ['name' => 'is_system', 'type' => 'boolean', 'description' => 'Whether the product supplied the category with the account. A supplied category may be moved and retired, never renamed and never deleted, because the wording it falls back to belongs to the product.'],
    ['name' => 'is_active', 'type' => 'boolean', 'description' => 'Whether the category and the types under it are offered when a new relationship is written. False removes the whole category from that choice and touches nothing already written: every relationship stays readable.'],
    ['name' => 'type_count', 'type' => 'integer', 'description' => 'How many relationship types sit under the category, retired ones counted. It is what decides whether the category may be deleted, since one still holding a type cannot go.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the category was added, as a count of seconds since 1970 rather than a written date.'],
    ['name' => 'updated_at', 'type' => 'timestamp', 'description' => 'When it last changed, as a count of seconds. Equal to created_at until it first changes.'],
];

return [
    'name' => 'Relationship categories',
    'description' => 'The categories relationship types are filed under, which order the vocabulary rather than deciding anything.',
    'sections' => [
        [
            'id' => 'relationship-categories-index',
            'returnFields' => ApiDocsFields::page('relationship_category', 'category', 'categories', $categoryAttributes),
            'title' => 'List relationship categories',
            'label' => 'List relationship categories',
            'method' => 'GET',
            'path' => '/relationship-categories',
            'description' => 'List the categories your account files its relationship types under, in the order you arranged them.',
            'body' => [
                'A category groups the list and nothing more. Moving a type into Family does not make it genealogical, and moving a genealogical one out of it does not take that behaviour away.',
                'Every account starts with five: Family, Romantic, Friendship, Professional and Other. Those five are marked with is_system, may be moved and retired, and can never be renamed or deleted. Any category you add yourself carries none of those restrictions.',
                'Retired categories are listed with the rest, because one that is off is meant to be found again and switched back on.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'queryParams' => $pageParams,
            'returns' => 'A page of relationship category objects, and how many there are in all.',
            'response' => [
                'data' => [$category, $otherCategory],
                'meta' => ['page' => 1, 'per_page' => 25, 'total' => 2],
            ],
        ],
        [
            'id' => 'relationship-categories-show',
            'returnFields' => ApiDocsFields::one('relationship_category', 'category', $categoryAttributes),
            'title' => 'Get a relationship category',
            'label' => 'Get a relationship category',
            'method' => 'GET',
            'path' => '/relationship-categories/{relationshipCategory}',
            'examplePath' => '/relationship-categories/7d1f4a83-2c06-4e59-9b27-8f3a5d0e6c14',
            'description' => 'Retrieve one category: what it is called, where it sits, and how many types it holds.',
            'body' => [
                'The types themselves are not in here. Ask for them at the address below, or list every type of the account at once.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $identifier,
            'returns' => 'A relationship category object. A category belonging to somebody else answers 404, which is also what a category that never existed answers, and what a deleted one answers.',
            'response' => ['data' => $category],
        ],
        [
            'id' => 'relationship-categories-create',
            'returnFields' => ApiDocsFields::one('relationship_category', 'category', $categoryAttributes),
            'title' => 'Add a relationship category',
            'label' => 'Add a relationship category',
            'method' => 'POST',
            'path' => '/relationship-categories',
            'description' => 'Add a category of your own, when the five the product supplies do not sort your list the way you think of it.',
            'body' => [
                'The category goes at the end of the list and is offered straight away. It holds nothing until you file types under it, and a category holding nothing is a perfectly ordinary state: Other is supplied that way.',
                'A category does no work beyond grouping. Adding one called Family of my own does not make anything filed under it genealogical.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'bodyParams' => [
                ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => 'What the category is called. Up to 255 characters. Markup and surrounding blank space are stripped, and a name left with nothing in it is refused.', 'example' => 'Neighbourhood'],
            ],
            'returns' => 'The relationship category object that was added.',
            'responseStatus' => 201,
            'response' => ['data' => $otherCategory],
        ],
        [
            'id' => 'relationship-categories-update',
            'returnFields' => ApiDocsFields::one('relationship_category', 'category', $categoryAttributes),
            'title' => 'Rename a relationship category',
            'label' => 'Rename a relationship category',
            'method' => 'PUT',
            'path' => '/relationship-categories/{relationshipCategory}',
            'examplePath' => '/relationship-categories/b42e9c07-5a18-4d73-8e60-1c9f2b7a4d35',
            'description' => 'Change what one of your own categories is called.',
            'body' => [
                'A category the product supplied cannot be renamed, and answers 422. Its wording belongs to the product and reads in the language of whoever is looking, which is what would be lost. Move it, retire it, or file your types under a category of your own instead.',
                'The types under the category are untouched by a rename, and so is where the category sits.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $identifier,
            'bodyParams' => [
                ['name' => 'name', 'type' => 'string', 'required' => false, 'description' => 'What the category is called. Up to 255 characters. It cannot be null.', 'example' => 'Neighbourhood'],
            ],
            'returns' => 'The relationship category object as it now reads. A supplied category answers 422.',
            'response' => ['data' => $otherCategory],
        ],
        [
            'id' => 'relationship-categories-destroy',
            'title' => 'Delete a relationship category',
            'label' => 'Delete a relationship category',
            'method' => 'DELETE',
            'path' => '/relationship-categories/{relationshipCategory}',
            'examplePath' => '/relationship-categories/b42e9c07-5a18-4d73-8e60-1c9f2b7a4d35',
            'description' => 'Remove a category of your own that holds nothing.',
            'body' => [
                'Two categories cannot go. One the product supplied stays. One that still holds a type stays, and the answer says how many it holds, because removing it would take those types with it. Move them elsewhere first, or retire the category instead.',
                'A deletion is recoverable, so an accident is not the end of anything.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $identifier,
            'returns' => 'Nothing. A supplied category, and one still holding a type, each answer 422 naming that reason, and one belonging to somebody else answers 404.',
            'responseStatus' => 204,
            'response' => [],
        ],
        [
            'id' => 'relationship-categories-activation',
            'returnFields' => ApiDocsFields::one('relationship_category', 'category', $categoryAttributes),
            'title' => 'Offer or retire a relationship category',
            'label' => 'Offer or retire a relationship category',
            'method' => 'PUT',
            'path' => '/relationship-categories/{relationshipCategory}/activation',
            'examplePath' => '/relationship-categories/b42e9c07-5a18-4d73-8e60-1c9f2b7a4d35/activation',
            'description' => 'Say whether a category, and every type under it, may still be chosen for a new relationship.',
            'body' => [
                'Retiring a category takes it out of the choice offered when somebody writes a relationship down. It is the tidiest way to put a run of types aside without deciding about each one, and it works on a category the product supplied as readily as on your own.',
                'Nothing already written is touched. Every relationship keeps reading with the words it was written with, and the category keeps the place it had, so offering it again puts it back where it was rather than at the end.',
                'Send the state you want rather than asking for a toggle, so sending the same value twice confirms it instead of inverting it.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $identifier,
            'bodyParams' => [
                ['name' => 'is_active', 'type' => 'boolean', 'required' => true, 'description' => 'True to offer the category again, false to retire it.', 'example' => 'false'],
            ],
            'returns' => 'The relationship category object in the state you asked for.',
            'response' => ['data' => $category],
        ],
        [
            'id' => 'relationship-categories-position',
            'returnFields' => ApiDocsFields::one('relationship_category', 'category', $categoryAttributes),
            'title' => 'Move a relationship category',
            'label' => 'Move a relationship category',
            'method' => 'PUT',
            'path' => '/relationship-categories/{relationshipCategory}/position',
            'examplePath' => '/relationship-categories/b42e9c07-5a18-4d73-8e60-1c9f2b7a4d35/position',
            'description' => 'Put a category somewhere else in the list, so the ones you reach for sit at the top.',
            'body' => [
                'Every category of your account is numbered from 1 again once the move is made, retired ones counted among them. A position past the end puts the category last rather than being refused.',
                'A category the product supplied may be moved like any other. Where a category sits has no effect beyond the order things are read in.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $identifier,
            'bodyParams' => [
                ['name' => 'position', 'type' => 'integer', 'required' => true, 'description' => 'Where the category should sit, counting from 1.', 'example' => '1'],
            ],
            'returns' => 'The relationship category object in its new place.',
            'response' => ['data' => $category],
        ],
    ],
];
