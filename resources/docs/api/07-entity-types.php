<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();

$entityType = [
    'type' => 'entity_type',
    'id' => '4c2b9f10-5e37-4a6d-8b91-0f7c3d2a8e64',
    'attributes' => [
        'slug' => 'animal',
        'name_singular' => 'Animal',
        'name_plural' => 'Animals',
        'description' => 'Every creature the family keeps.',
        'icon' => 'profile',
        'colour' => '#4f8a49',
        'position' => 1,
        'is_active' => true,
        'entity_count' => 12,
        'created_at' => 1756252800,
        'updated_at' => 1756339200,
    ],
    'links' => [
        'self' => $base.'/entity-types/4c2b9f10-5e37-4a6d-8b91-0f7c3d2a8e64',
    ],
];

$otherEntityType = [
    'type' => 'entity_type',
    'id' => 'e8a51d73-9c04-4b28-a6f5-31b7e0c4d925',
    'attributes' => [
        'slug' => 'wine-bottle',
        'name_singular' => 'Wine bottle',
        'name_plural' => 'Wine bottles',
        'description' => null,
        'icon' => 'key',
        'colour' => '#9a6a24',
        'position' => 2,
        'is_active' => false,
        'entity_count' => 0,
        'created_at' => 1756166400,
        'updated_at' => null,
    ],
    'links' => [
        'self' => $base.'/entity-types/e8a51d73-9c04-4b28-a6f5-31b7e0c4d925',
    ],
];

$pageParams = [
    [
        'name' => 'page',
        'type' => 'integer',
        'required' => false,
        'description' => 'Which page to answer with.',
        'default' => '1',
    ],
    [
        'name' => 'per_page',
        'type' => 'integer',
        'required' => false,
        'description' => 'How many types one page holds, between 1 and 100. A number outside the range is brought back into it rather than refused.',
        'default' => '25',
    ],
];

$identifier = [
    [
        'name' => 'entityType',
        'type' => 'uuid',
        'required' => true,
        'description' => 'The identifier of the type, which is the id it comes back with. A path that is not a uuid answers 404 without a query being run, so neither a sequential number nor the slug reaches anything here.',
    ],
];

$icons = 'One of profile, logs, security, preferences, key, device, info, life event, mail, sun, moon or check.';

$colours = 'Exactly one of #3f5fd0, #4f8a49, #9a6a24, #9a3f2b, #5d5f96 or #1d1d20, written in lower case.';

$entityTypeAttributes = [
    ['name' => 'slug', 'type' => 'string', 'description' => 'How the type is addressed in the application itself, in lower case letters, numbers and dashes. It is settled when the type is declared and never rebuilt, so a link that already went out keeps working through any rename. Use it to build a browser link; use the id for every call here.'],
    ['name' => 'name_singular', 'type' => 'string', 'description' => 'What one of these is called: Animal, Company, Recipe. Up to 255 characters. Reduced to plain text when it was written down, so no markup survives here.'],
    ['name' => 'name_plural', 'type' => 'string', 'description' => 'What several of them are called. Stored rather than derived from the singular, because a plural derived in English is wrong in most other languages.'],
    ['name' => 'description', 'type' => 'string', 'description' => 'What the type is for, up to 65535 characters, or null when nobody said.'],
    ['name' => 'icon', 'type' => 'string', 'description' => $icons.' It is a name rather than a drawing, so the picture lives in the application and the type stores a word.'],
    ['name' => 'colour', 'type' => 'string', 'description' => $colours.' It is how types are told apart at a glance in the navigation, and it is decoration: nothing depends on it.'],
    ['name' => 'position', 'type' => 'integer', 'description' => 'Where the type sits in the vault navigation, counting from 1. It is the order you arranged, and it is the order the list endpoint answers in. Types that may no longer be filed under are numbered along with the rest, so one switched back on finds the place it had.'],
    ['name' => 'is_active', 'type' => 'boolean', 'description' => 'Whether new records may still be filed under the type. False stops the button that adds another and touches nothing already filed: every record stays readable and stays editable.'],
    ['name' => 'entity_count', 'type' => 'integer', 'description' => 'How many records of this kind exist, across every vault of your account. It is a running count kept as records are written down and deleted, rather than something counted when you ask, and it is what decides whether the type may be deleted.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the type was declared, as a count of seconds since 1970 rather than a written date.'],
    ['name' => 'updated_at', 'type' => 'timestamp', 'description' => 'When it last changed, as a count of seconds, or null if it has not changed since it was declared.'],
];

$entity = [
    'type' => 'entity',
    'id' => '9b1e6c42-7a35-4f08-b2d9-5c8e41a7f031',
    'attributes' => [
        'name' => 'Mose’s goat',
        'entity_type_id' => '4c2b9f10-5e37-4a6d-8b91-0f7c3d2a8e64',
        'initials' => 'MG',
        'avatar_colour' => '#22c55e',
        'avatar' => null,
        'is_archived' => false,
        'structure_version_id' => 12,
        'template_name' => 'Basic animal',
        'created_at' => 1758844800,
        'updated_at' => 1758844800,
    ],
];

$entityAttributes = [
    ['name' => 'name', 'type' => 'string', 'description' => 'What the record is called, up to 255 characters. It is how the record is listed and sorted, and it is reduced to plain text when it is written down.'],
    ['name' => 'entity_type_id', 'type' => 'uuid', 'description' => 'The kind of record it is. It never changes.'],
    ['name' => 'initials', 'type' => 'string', 'description' => 'The first letter of the first two words of the name, drawn on the generated avatar.'],
    ['name' => 'avatar_colour', 'type' => 'string', 'description' => 'The background the generated avatar is drawn on. It is picked when the record is written down and kept afterwards.'],
    ['name' => 'avatar', 'type' => 'object', 'description' => 'The picture standing in for the record, with its id and url, or null when it is drawn as its initials.'],
    ['name' => 'is_archived', 'type' => 'boolean', 'description' => 'Whether the record was put away. An archived record is still listed.'],
    ['name' => 'structure_version_id', 'type' => 'integer', 'description' => 'The exact version of the template the record was written on. A record stays on that version until somebody moves it.'],
    ['name' => 'template_name', 'type' => 'string', 'description' => 'The name of the template that version belongs to.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the record was written down, as a count of seconds since 1970.'],
    ['name' => 'updated_at', 'type' => 'timestamp', 'description' => 'When it last changed, as a count of seconds, or null.'],
];

$entityPathParams = [
    [
        'name' => 'vault',
        'type' => 'string',
        'required' => true,
        'description' => 'The vault the records live in, named either by the readable name it reports as slug or by the identifier it reports as id. Both answer.',
    ],
    [
        'name' => 'entityType',
        'type' => 'uuid',
        'required' => true,
        'description' => 'The kind of record. A type the vault does not use, which is one with no published template set for it there and no record in it, answers 404.',
    ],
];

return [
    'name' => 'Kinds of record',
    'description' => 'The sorts of thing a vault keeps besides people, each with its own template and its own place in the order.',
    'sections' => [
        [
            'id' => 'entity-types-index',
            'returnFields' => ApiDocsFields::page('entity_type', 'type', 'types', $entityTypeAttributes),
            'title' => 'List entity types',
            'label' => 'List entity types',
            'method' => 'GET',
            'path' => '/entity-types',
            'description' => 'List the kinds of object your account invented, in the order you arranged them.',
            'body' => [
                'A type is the noun you reached for when nothing the product ships with fits: Animal, Company, Recipe, Wine bottle. It belongs to your account rather than to one vault, so a type declared once is visible in every vault you hold.',
                'The order is the one you gave them, which is the order they are listed in down the side of a vault. It is not the order they were declared in. Types that may no longer be filed under are listed with the rest, because one that is off is meant to be found again and switched back on.',
                'A deleted type is never listed. An account that invented nothing answers with an empty list rather than an error, since that is the state every account starts in.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'queryParams' => $pageParams,
            'returns' => 'A page of entity type objects, and how many there are in all.',
            'response' => [
                'data' => [$entityType, $otherEntityType],
                'meta' => [
                    'page' => 1,
                    'per_page' => 25,
                    'total' => 2,
                ],
            ],
        ],
        [
            'id' => 'entity-types-show',
            'returnFields' => ApiDocsFields::one('entity_type', 'type', $entityTypeAttributes),
            'title' => 'Get an entity type',
            'label' => 'Get an entity type',
            'method' => 'GET',
            'path' => '/entity-types/{entityType}',
            'examplePath' => '/entity-types/4c2b9f10-5e37-4a6d-8b91-0f7c3d2a8e64',
            'description' => 'Retrieve one kind of object: what it is called, how it is drawn, and how many records exist of it.',
            'body' => [
                'The type is only the noun. What one of its records holds is described separately, by a template, and the same type may have several of them: the family vault and a professional breeding vault can both handle Animals at completely different levels of detail. Nothing about that structure is in here.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => $identifier,
            'returns' => 'An entity type object. A type belonging to somebody else answers 404, which is also what a type that never existed answers, and what a deleted one answers.',
            'response' => ['data' => $entityType],
        ],
        [
            'id' => 'entity-types-create',
            'returnFields' => ApiDocsFields::one('entity_type', 'type', $entityTypeAttributes),
            'title' => 'Create a kind of record',
            'label' => 'Create a kind of record',
            'method' => 'POST',
            'path' => '/entity-types',
            'description' => 'Declare a kind of object your account wants to keep, when nothing the product ships with fits.',
            'body' => [
                'This writes down the noun and nothing else. What one of its records holds is described separately, by a template, so a type can exist for a while with nothing to show. The type goes at the end of your navigation.',
                'Both names are asked for rather than one being made from the other, because a plural derived in English is wrong in most other languages.',
                'The slug is settled here and never rebuilt afterwards. Choose it, and what you send is cleaned the same way a derived one is, so nothing can put a space or an accent in an address. Say nothing, and it is made from the singular name. A slug already taken in your account is refused, and a type you deleted does not release the one it had, so a link that went out cannot later land on something else.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'bodyParams' => [
                [
                    'name' => 'name_singular',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'What one of these is called. Up to 255 characters. Markup and surrounding blank space are stripped.',
                    'example' => 'Animal',
                ],
                [
                    'name' => 'name_plural',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'What several of them are called. Up to 255 characters.',
                    'example' => 'Animals',
                ],
                [
                    'name' => 'description',
                    'type' => 'string',
                    'required' => false,
                    'description' => 'What the type is for. Up to 65535 characters. Absent, empty or null all mean it has none.',
                    'example' => 'Every creature the family keeps.',
                ],
                [
                    'name' => 'icon',
                    'type' => 'string',
                    'required' => false,
                    'description' => $icons.' Absent means profile.',
                    'default' => 'profile',
                ],
                [
                    'name' => 'colour',
                    'type' => 'string',
                    'required' => false,
                    'description' => $colours.' Absent means #3f5fd0.',
                    'default' => '#3f5fd0',
                ],
                [
                    'name' => 'is_active',
                    'type' => 'boolean',
                    'required' => false,
                    'description' => 'Whether records may be filed under the type straight away. Absent means true. Send false to declare a type before anybody is meant to use it.',
                    'default' => 'true',
                ],
                [
                    'name' => 'slug',
                    'type' => 'string',
                    'required' => false,
                    'description' => 'How the type is addressed in the application itself: lower case letters, numbers and single dashes, up to 64 characters. Absent means one made from the singular name. A slug already taken in your account is refused, and that counts the slugs of types you have deleted.',
                    'example' => 'animal',
                ],
            ],
            'returns' => 'The new entity type object. Keep its id: it is the address every other call here takes.',
            'responseStatus' => 201,
            'response' => ['data' => $entityType],
        ],
        [
            'id' => 'entity-types-update',
            'returnFields' => ApiDocsFields::one('entity_type', 'type', $entityTypeAttributes),
            'title' => 'Update an entity type',
            'label' => 'Update an entity type',
            'method' => 'PUT',
            'path' => '/entity-types/{entityType}',
            'examplePath' => '/entity-types/4c2b9f10-5e37-4a6d-8b91-0f7c3d2a8e64',
            'description' => 'Change what a kind of object is called, what it is for, and how it is drawn.',
            'body' => [
                'This replaces the wording of the type rather than patching it, so state all of it. A field you leave out is refused rather than quietly reset, which is why the icon and the colour are required and the description has to be sent even when it is null. Renaming a type can therefore never lose its description by accident.',
                'The slug is left alone. A rename is a change of wording rather than a change of thing: Animal becoming Pet is the same type, and every link that already went out keeps working. Where the type sits in your navigation, whether records may still be filed under it, and everything already filed under it are each untouched here.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => $identifier,
            'bodyParams' => [
                [
                    'name' => 'name_singular',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'What one of these is called. Up to 255 characters.',
                    'example' => 'Pet',
                ],
                [
                    'name' => 'name_plural',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'What several of them are called. Up to 255 characters.',
                    'example' => 'Pets',
                ],
                [
                    'name' => 'description',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'What the type is for, up to 65535 characters. Send null to say it has none. It has to be stated either way, because leaving it out would erase it.',
                    'example' => 'Every creature the family keeps.',
                ],
                [
                    'name' => 'icon',
                    'type' => 'string',
                    'required' => true,
                    'description' => $icons,
                    'example' => 'profile',
                ],
                [
                    'name' => 'colour',
                    'type' => 'string',
                    'required' => true,
                    'description' => $colours,
                    'example' => '#4f8a49',
                ],
            ],
            'returns' => 'The entity type object as it now reads. A type belonging to somebody else answers 404 and is left as it was.',
            'response' => ['data' => $entityType],
        ],
        [
            'id' => 'entity-types-destroy',
            'title' => 'Delete an entity type',
            'label' => 'Delete an entity type',
            'method' => 'DELETE',
            'path' => '/entity-types/{entityType}',
            'examplePath' => '/entity-types/4c2b9f10-5e37-4a6d-8b91-0f7c3d2a8e64',
            'description' => 'Delete a kind of object, which is only allowed while no record of it exists.',
            'body' => [
                'A type that still holds records refuses to go, and answers 422 saying how many it holds. Taking a hundred animals down with the word "Animal" is not something one call should be able to do. The two ways forward are to delete those records first, deliberately, or to stop the type being offered and keep everything filed under it.',
                'The deletion is soft, so nothing is destroyed, but no endpoint here brings a type back today. The slug is not released with it either: a link that went out pointing at this type must not later land on a different one, so declaring a new type with the same slug is refused.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => $identifier,
            'returns' => 'Nothing at all. A type that still holds records answers 422 and stays. A type belonging to somebody else answers 404, and so does one you have already deleted, so calling this twice does not report a second deletion.',
            'responseStatus' => 204,
        ],
        [
            'id' => 'entity-types-activation',
            'returnFields' => ApiDocsFields::one('entity_type', 'type', $entityTypeAttributes),
            'title' => 'Offer or stop offering an entity type',
            'label' => 'Offer or stop offering',
            'method' => 'PUT',
            'path' => '/entity-types/{entityType}/activation',
            'examplePath' => '/entity-types/4c2b9f10-5e37-4a6d-8b91-0f7c3d2a8e64/activation',
            'description' => 'Say whether records may still be filed under a kind of object.',
            'body' => [
                'This is what a type in use gets instead of a deletion. Stopping one touches nothing already filed under it: every record stays readable and stays editable, and only the button that adds another goes. An account that has finished cataloguing its wine bottles wants the bottles, not the button.',
                'The place the type has in your navigation is kept, so offering it again puts it back where it was rather than at the end.',
                'It has its own address because it is a decision about your account rather than a correction to the wording of the type, and it cannot therefore be made by accident while renaming one. Send the state you want rather than asking for a toggle: sending the same state twice confirms it instead of inverting it.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => $identifier,
            'bodyParams' => [
                [
                    'name' => 'is_active',
                    'type' => 'boolean',
                    'required' => true,
                    'description' => 'True to let records be filed under the type again, false to stop new ones being filed. The state you want, not a change: sending the state the type is already in changes nothing and answers with the type.',
                    'example' => false,
                ],
            ],
            'returns' => 'The entity type object as it now reads. A type belonging to somebody else answers 404 and is left as it was.',
            'response' => ['data' => $entityType],
        ],
        [
            'id' => 'entity-types-position',
            'returnFields' => ApiDocsFields::one('entity_type', 'type', $entityTypeAttributes),
            'title' => 'Move an entity type',
            'label' => 'Move an entity type',
            'method' => 'PUT',
            'path' => '/entity-types/{entityType}/position',
            'examplePath' => '/entity-types/4c2b9f10-5e37-4a6d-8b91-0f7c3d2a8e64/position',
            'description' => 'Move a kind of object in your navigation, so the ones you open daily are the ones you reach first.',
            'body' => [
                'The type is taken out and put back at the position you asked for, and every type of your account is then numbered from one again. Ask for a position past the end and it goes last. Ask for the position it already holds and nothing moves.',
                'Types nobody may file records under are numbered along with the rest, since one you offer again has to find the place it had.',
                'The list endpoint answers in this order, so moving a type changes what every caller reads next.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => $identifier,
            'bodyParams' => [
                [
                    'name' => 'position',
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'Where the type should sit, counting from 1. A number past the end puts it last. Below 1 is refused, because there is no place before the first.',
                    'example' => 1,
                ],
            ],
            'returns' => 'The entity type object as it now reads, carrying its new position. A type belonging to somebody else answers 404 and nothing is renumbered.',
            'response' => ['data' => $entityType],
        ],
        [
            'id' => 'entities-index',
            'returnFields' => ApiDocsFields::page('entity', 'record', 'records', $entityAttributes, false),
            'title' => 'List the records of a kind',
            'label' => 'List records',
            'method' => 'GET',
            'path' => '/vaults/{vault}/entity-types/{entityType}/entities',
            'examplePath' => '/vaults/schrute-farms/entity-types/4c2b9f10-5e37-4a6d-8b91-0f7c3d2a8e64/entities',
            'description' => 'List the records of one kind kept in one vault, by name.',
            'body' => [
                'Archived records are listed with the others and marked. A record of the same kind in another vault is never listed here.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => $entityPathParams,
            'queryParams' => $pageParams,
            'returns' => 'A page of entity objects, and how many there are in all. A vault or a type belonging to somebody else answers 404.',
            'response' => [
                'data' => [$entity],
                'meta' => [
                    'page' => 1,
                    'per_page' => 25,
                    'total' => 1,
                ],
            ],
        ],
        [
            'id' => 'entities-create',
            'returnFields' => ApiDocsFields::one('entity', 'record', $entityAttributes, false),
            'title' => 'Create a record',
            'label' => 'Create a record',
            'method' => 'POST',
            'path' => '/vaults/{vault}/entity-types/{entityType}/entities',
            'examplePath' => '/vaults/schrute-farms/entity-types/4c2b9f10-5e37-4a6d-8b91-0f7c3d2a8e64/entities',
            'description' => 'Write down a record of one kind in one vault, with what its template asks.',
            'body' => [
                'The record is written on the published version of the template the vault uses for this kind. Everything is written together or nothing is: a value refused in any section leaves no record behind.',
                'A field you leave out starts with the default its template gives it, as it would on a new contact. Send it empty to leave it empty.',
                'A type the vault does not show answers 404. A type the vault still shows because it holds records of it answers 422 with the reason on name when the type was turned off, when the vault has no published template for it, or when that template requires something that cannot be filled in here.',
                'A relation field takes the id of the record it names: a contact, or a record of the kind the field points at. It has to be in the same vault, and anything else answers 422 on that field.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => $entityPathParams,
            'bodyParams' => [
                [
                    'name' => 'name',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'What the record is called, up to 255 characters.',
                    'example' => 'Mose’s goat',
                ],
                [
                    'name' => 'fields',
                    'type' => 'object',
                    'required' => false,
                    'description' => 'The values, keyed by the id of the field they fill in, each checked against what its field accepts. A field left out writes nothing. Files are sent as multipart under fields[id].',
                    'example' => ['381' => 'Goat'],
                ],
                [
                    'name' => 'labels',
                    'type' => 'object',
                    'required' => false,
                    'description' => 'The label picked beside a value, keyed by field id, for a field that carries one.',
                ],
                [
                    'name' => 'label_wordings',
                    'type' => 'object',
                    'required' => false,
                    'description' => 'The wording typed when the picked label is the open one, keyed by field id.',
                ],
                [
                    'name' => 'year_is_unknown',
                    'type' => 'object',
                    'required' => false,
                    'description' => 'True for a recurring date whose year nobody knows, keyed by field id.',
                ],
                [
                    'name' => 'month_is_unknown',
                    'type' => 'object',
                    'required' => false,
                    'description' => 'True for a recurring date whose month nobody knows, keyed by field id.',
                ],
            ],
            'returns' => 'The entity object that was written down. A vault or a type belonging to somebody else answers 404.',
            'responseStatus' => 201,
            'response' => ['data' => $entity],
        ],
    ],
];
