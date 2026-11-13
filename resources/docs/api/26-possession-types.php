<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();
$application = rtrim((string) config('app.url'), '/');

$rateLimit = 'Sixty requests per minute, per user, shared by every key and device.';

$webLink = 'The screen where the possession type is edited in the application, which opens for somebody signed in rather than for a key. It is the link to give a person.';

$typeId = '9c1d4e7a-3b52-4f86-a0d9-2e7b6c418f35';
$groupId = '4f2a8c61-7d93-4e05-b1c8-93a6d5e20b17';
$paymentId = 'b7e04a93-1c6f-4d28-9a35-6f8e2d1c4b90';
$serialId = '2d8b5f1e-6a47-4c93-8e02-1b9f7c3a5d64';
$conditionId = 'e5c37a28-9f14-4b6d-a083-7d2e1f9b6c45';

$officeEquipment = [
    'type' => 'possession_type',
    'id' => $typeId,
    'attributes' => [
        'name' => 'Office equipment',
        'colour' => '#2f7f86',
        'icon' => 'briefcase',
        'field_count' => 3,
        'groups' => [
            [
                'id' => $groupId,
                'name' => 'Purchase',
                'position' => 1,
                'fields' => [
                    ['id' => $paymentId, 'name' => 'Payment method', 'kind' => 'select', 'options' => ['Cash', 'Card'], 'position' => 1, 'group_id' => $groupId],
                ],
            ],
        ],
        'fields' => [
            ['id' => $conditionId, 'name' => 'Condition', 'kind' => 'select', 'options' => ['New', 'Used', 'Broken'], 'position' => 1, 'group_id' => null],
            ['id' => $serialId, 'name' => 'Serial number', 'kind' => 'text', 'options' => null, 'position' => 2, 'group_id' => null],
        ],
        'created_at' => 1761436800,
        'updated_at' => 1761440400,
    ],
    'links' => [
        'self' => "{$base}/possession-types/{$typeId}",
        'web' => "{$application}/settings/account/possession-types/{$typeId}/edit",
    ],
];

$fieldAttributes = static fn (string $prefix): array => [
    ['name' => "{$prefix}[]", 'type' => 'object', 'description' => 'One field.'],
    ['name' => "{$prefix}[].id", 'type' => 'uuid', 'description' => 'The identifier of the field.'],
    ['name' => "{$prefix}[].name", 'type' => 'string', 'description' => 'What the field is called, such as Serial number.'],
    ['name' => "{$prefix}[].kind", 'type' => 'string', 'description' => 'What the field holds: text, number, date, boolean, select, or rating from one to five.'],
    ['name' => "{$prefix}[].options", 'type' => 'array', 'description' => 'The choices of a select field, in order, or null for every other kind.'],
    ['name' => "{$prefix}[].options[]", 'type' => 'string', 'description' => 'One choice.'],
    ['name' => "{$prefix}[].position", 'type' => 'integer', 'description' => 'Its place among its siblings, counting from 1.'],
    ['name' => "{$prefix}[].group_id", 'type' => 'uuid', 'description' => 'The group it sits in, or null when it stands alone.'],
];

$typeAttributes = [
    ['name' => 'name', 'type' => 'string', 'description' => 'What the kind of thing is called: office equipment, a vehicle, a musical instrument.'],
    ['name' => 'colour', 'type' => 'string', 'description' => 'One of the eight vault colours, as a hexadecimal value.'],
    ['name' => 'icon', 'type' => 'string', 'description' => 'One of the glyphs of the catalogue (the same list sections and possession conditions use), or null. A type without one is drawn with a blank page.'],
    ['name' => 'field_count', 'type' => 'integer', 'description' => 'How many fields the type has, grouped or not.'],
    ['name' => 'groups', 'type' => 'array', 'description' => 'The groups of fields, in order.'],
    ['name' => 'groups[]', 'type' => 'object', 'description' => 'One group.'],
    ['name' => 'groups[].id', 'type' => 'uuid', 'description' => 'The identifier of the group.'],
    ['name' => 'groups[].name', 'type' => 'string', 'description' => 'What the group is called, such as Purchase.'],
    ['name' => 'groups[].position', 'type' => 'integer', 'description' => 'Its place among the groups, counting from 1.'],
    ['name' => 'groups[].fields', 'type' => 'array', 'description' => 'The fields of the group, in order.'],
    ...$fieldAttributes('groups[].fields'),
    ['name' => 'fields', 'type' => 'array', 'description' => 'The fields standing outside any group, in order.'],
    ...$fieldAttributes('fields'),
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the type was added, as a count of seconds since 1970.'],
    ['name' => 'updated_at', 'type' => 'timestamp', 'description' => 'When its name, colour or icon last changed, as a count of seconds, or null.'],
];

$newType = $officeEquipment;
$newType['attributes']['field_count'] = 0;
$newType['attributes']['groups'] = [];
$newType['attributes']['fields'] = [];
$newType['attributes']['updated_at'] = 1761436800;

$newTypeAttributes = [
    ['name' => 'name', 'type' => 'string', 'description' => 'What the kind of thing is called.'],
    ['name' => 'colour', 'type' => 'string', 'description' => 'One of the eight vault colours, as a hexadecimal value.'],
    ['name' => 'icon', 'type' => 'string', 'description' => 'One of the glyphs of the catalogue (the same list sections and possession conditions use), or null. A type without one is drawn with a blank page.'],
    ['name' => 'field_count', 'type' => 'integer', 'description' => 'How many fields the type has, which is 0 for a new one.'],
    ['name' => 'groups', 'type' => 'array', 'description' => 'The groups of fields, an empty list for a new type.'],
    ['name' => 'fields', 'type' => 'array', 'description' => 'The standalone fields, an empty list for a new type.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the type was added, as a count of seconds since 1970.'],
    ['name' => 'updated_at', 'type' => 'timestamp', 'description' => 'When it last changed, as a count of seconds.'],
];

$one = ApiDocsFields::one('possession_type', 'possession type', $typeAttributes, webLink: $webLink);

$typeParam = ['name' => 'possessionType', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of the possession type. A type belonging to somebody else answers 404.'];
$groupParam = ['name' => 'group', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of the group. A group of another type answers 404.'];
$fieldParam = ['name' => 'field', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of the field. A field of another type answers 404.'];

$nameRefusal = 'Up to 255 characters. Markup and surrounding blank space are stripped, and a name left with nothing in it is refused.';

$fieldBody = [
    ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => "What the field is called. {$nameRefusal}", 'example' => 'Condition'],
    ['name' => 'kind', 'type' => 'string', 'required' => true, 'description' => 'One of text, number, date, boolean, select or rating.', 'example' => 'select'],
    ['name' => 'options', 'type' => 'array', 'required' => false, 'description' => 'The choices of a select field, in order, at most 200 of up to 255 characters each. Blank ones are dropped and a repeated one is refused. Ignored for every other kind.', 'example' => ['New', 'Used', 'Broken']],
];

$positionBody = [
    ['name' => 'position', 'type' => 'integer', 'required' => true, 'description' => 'Where it goes, counting from 1. A number past the end puts it last.', 'example' => 1],
];

$fileFields = static fn (string $prefix): array => [
    ['name' => "{$prefix}[]", 'type' => 'object', 'description' => 'One field.'],
    ['name' => "{$prefix}[].name", 'type' => 'string', 'description' => 'The name of the field.'],
    ['name' => "{$prefix}[].kind", 'type' => 'string', 'description' => 'What the field holds.'],
    ['name' => "{$prefix}[].options", 'type' => 'array', 'description' => 'The choices, present on a select field only.'],
    ['name' => "{$prefix}[].options[]", 'type' => 'string', 'description' => 'One choice.'],
];

$file = [
    'format' => 'possession_type',
    'format_version' => 1,
    'possession_type' => [
        'name' => 'Office equipment',
        'colour' => '#2f7f86',
        'icon' => 'briefcase',
        'groups' => [
            ['name' => 'Purchase', 'fields' => [['name' => 'Payment method', 'kind' => 'select', 'options' => ['Cash', 'Card']]]],
        ],
        'fields' => [
            ['name' => 'Condition', 'kind' => 'select', 'options' => ['New', 'Used', 'Broken']],
            ['name' => 'Serial number', 'kind' => 'text'],
        ],
    ],
];

return [
    'name' => 'Possession types',
    'description' => 'The kinds of things your account owns, and the fields a thing of each kind carries.',
    'sections' => [
        [
            'id' => 'possession-types-index',
            'returnFields' => ApiDocsFields::page('possession_type', 'possession type', 'possession types', $typeAttributes, webLink: $webLink),
            'title' => 'List possession types',
            'method' => 'GET',
            'path' => '/possession-types',
            'description' => 'List every possession type of your account, sorted by name, each with its groups and fields in order.',
            'body' => ['A possession type belongs to the account, not to a vault, as locations do.'],
            'rateLimit' => $rateLimit,
            'queryParams' => [
                ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Which page to answer with.', 'default' => '1'],
                ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'How many types one page holds, between 1 and 100. A number outside the range is brought back into it.', 'default' => '25'],
            ],
            'returns' => 'A page of possession type objects, and how many there are in all. An account with none answers an empty list.',
            'response' => ['data' => [$officeEquipment], 'meta' => ['page' => 1, 'per_page' => 25, 'total' => 1]],
        ],
        [
            'id' => 'possession-types-show',
            'returnFields' => $one,
            'title' => 'Get a possession type',
            'method' => 'GET',
            'path' => '/possession-types/{possessionType}',
            'examplePath' => "/possession-types/{$typeId}",
            'description' => 'Retrieve one possession type with its groups and fields.',
            'rateLimit' => $rateLimit,
            'pathParams' => [$typeParam],
            'returns' => 'A possession type object. A type belonging to somebody else answers 404.',
            'response' => ['data' => $officeEquipment],
        ],
        [
            'id' => 'possession-types-create',
            'returnFields' => ApiDocsFields::one('possession_type', 'possession type', $newTypeAttributes, webLink: $webLink),
            'title' => 'Add a possession type',
            'method' => 'POST',
            'path' => '/possession-types',
            'description' => 'Add a kind of thing your account owns. Add its groups and fields afterwards.',
            'body' => ['The change is written to the account log.'],
            'rateLimit' => $rateLimit,
            'bodyParams' => [
                ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => "What the kind of thing is called. {$nameRefusal}", 'example' => 'Office equipment'],
                ['name' => 'colour', 'type' => 'string', 'required' => false, 'description' => 'One of the vault colours: #3f5fd0, #4f8a49, #9a6a24, #9a3f2b, #6b4fa8, #2f7f86, #a8447a or #4a4a52. Blue when left out.', 'example' => '#2f7f86'],
                ['name' => 'icon', 'type' => 'string', 'required' => false, 'description' => 'One of the glyphs of the catalogue. Anything else answers 422. None when left out.', 'example' => 'briefcase'],
            ],
            'returns' => 'The possession type object that was added, with no group and no field yet.',
            'responseStatus' => 201,
            'response' => ['data' => $newType],
        ],
        [
            'id' => 'possession-types-update',
            'returnFields' => $one,
            'title' => 'Change a possession type',
            'method' => 'PUT',
            'path' => '/possession-types/{possessionType}',
            'examplePath' => "/possession-types/{$typeId}",
            'description' => 'Rename a possession type and set its colour and its icon. The name and the colour are required; the request replaces the whole type, so an icon left out is removed.',
            'body' => ['The change is written to the account log.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$typeParam],
            'bodyParams' => [
                ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => "The new name. {$nameRefusal}", 'example' => 'Office equipment'],
                ['name' => 'colour', 'type' => 'string', 'required' => true, 'description' => 'One of the vault colours, as a hexadecimal value.', 'example' => '#2f7f86'],
                ['name' => 'icon', 'type' => 'string', 'required' => false, 'description' => 'One of the glyphs of the catalogue, or null for none. Anything else answers 422.', 'example' => 'briefcase'],
            ],
            'returns' => 'The possession type object as it now reads.',
            'response' => ['data' => $officeEquipment],
        ],
        [
            'id' => 'possession-types-destroy',
            'title' => 'Delete a possession type',
            'method' => 'DELETE',
            'path' => '/possession-types/{possessionType}',
            'examplePath' => "/possession-types/{$typeId}",
            'description' => 'Delete a possession type with its groups, its fields, and every possession of that type with the values it held.',
            'body' => ['The deletion is final. The account log keeps the name of the type and, when possessions went with it, how many.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$typeParam],
            'returns' => 'Nothing. A type belonging to somebody else answers 404.',
            'responseStatus' => 204,
            'response' => [],
        ],
        [
            'id' => 'possession-types-export',
            'returnFields' => [
                ['name' => 'format', 'type' => 'string', 'description' => 'Always possession_type.'],
                ['name' => 'format_version', 'type' => 'integer', 'description' => 'The version of the format that wrote the file.'],
                ['name' => 'possession_type', 'type' => 'object', 'description' => 'The type itself.'],
                ['name' => 'possession_type.name', 'type' => 'string', 'description' => 'Its name.'],
                ['name' => 'possession_type.colour', 'type' => 'string', 'description' => 'Its colour.'],
                ['name' => 'possession_type.icon', 'type' => 'string', 'description' => 'Its icon, or null. A file without it imports with no icon; a glyph outside the catalogue is a problem.'],
                ['name' => 'possession_type.groups', 'type' => 'array', 'description' => 'Its groups, in order.'],
                ['name' => 'possession_type.groups[]', 'type' => 'object', 'description' => 'One group.'],
                ['name' => 'possession_type.groups[].name', 'type' => 'string', 'description' => 'The name of the group.'],
                ['name' => 'possession_type.groups[].fields', 'type' => 'array', 'description' => 'The fields of the group, in order.'],
                ...$fileFields('possession_type.groups[].fields'),
                ['name' => 'possession_type.fields', 'type' => 'array', 'description' => 'The fields standing outside any group, in order.'],
                ...$fileFields('possession_type.fields'),
            ],
            'title' => 'Export a possession type',
            'method' => 'GET',
            'path' => '/possession-types/{possessionType}/export',
            'examplePath' => "/possession-types/{$typeId}/export",
            'description' => 'Write a possession type out as a file you can import again, here or in another account.',
            'body' => ['The file holds the structure only: no thing you own and no value you wrote.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$typeParam],
            'returns' => 'The file, as a JSON object. A type belonging to somebody else answers 404.',
            'response' => $file,
        ],
        [
            'id' => 'possession-types-import',
            'returnFields' => $one,
            'title' => 'Import a possession type',
            'method' => 'POST',
            'path' => '/possession-types/import',
            'description' => 'Create a new possession type from a file written by the export. Nothing already in the account changes.',
            'body' => [
                'A file with any problem creates nothing, and answers 422 on file with every problem listed, such as "Group 1 needs a name." or "The standalone fields, field 2 has an unknown kind."',
                'The file may be at most 100 KB, with 50 groups, 300 fields and 200 choices per field. A file naming no colour takes #4a4a52.',
            ],
            'rateLimit' => $rateLimit,
            'bodyParams' => [
                ['name' => 'file', 'type' => 'string', 'required' => true, 'description' => 'The whole file, as JSON text.', 'example' => json_encode($file)],
            ],
            'returns' => 'The possession type object that was created.',
            'responseStatus' => 201,
            'response' => ['data' => $officeEquipment],
        ],
        [
            'id' => 'possession-field-groups-create',
            'returnFields' => $one,
            'title' => 'Add a group of fields',
            'method' => 'POST',
            'path' => '/possession-types/{possessionType}/groups',
            'examplePath' => "/possession-types/{$typeId}/groups",
            'description' => 'Add a named group of fields to a possession type, placed after the other groups.',
            'rateLimit' => $rateLimit,
            'pathParams' => [$typeParam],
            'bodyParams' => [
                ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => "What the group is called. {$nameRefusal}", 'example' => 'Purchase'],
            ],
            'returns' => 'The possession type object, with the new group.',
            'responseStatus' => 201,
            'response' => ['data' => $officeEquipment],
        ],
        [
            'id' => 'possession-field-groups-update',
            'returnFields' => $one,
            'title' => 'Rename a group of fields',
            'method' => 'PUT',
            'path' => '/possession-types/{possessionType}/groups/{group}',
            'examplePath' => "/possession-types/{$typeId}/groups/{$groupId}",
            'description' => 'Rename a group of fields.',
            'rateLimit' => $rateLimit,
            'pathParams' => [$typeParam, $groupParam],
            'bodyParams' => [
                ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => "The new name. {$nameRefusal}", 'example' => 'Purchase'],
            ],
            'returns' => 'The possession type object as it now reads.',
            'response' => ['data' => $officeEquipment],
        ],
        [
            'id' => 'possession-field-groups-destroy',
            'title' => 'Remove a group of fields',
            'method' => 'DELETE',
            'path' => '/possession-types/{possessionType}/groups/{group}',
            'examplePath' => "/possession-types/{$typeId}/groups/{$groupId}",
            'description' => 'Remove a group and keep its fields: they become standalone fields of the type, after the ones already there.',
            'rateLimit' => $rateLimit,
            'pathParams' => [$typeParam, $groupParam],
            'returns' => 'Nothing.',
            'responseStatus' => 204,
            'response' => [],
        ],
        [
            'id' => 'possession-field-groups-position',
            'returnFields' => $one,
            'title' => 'Move a group of fields',
            'method' => 'PUT',
            'path' => '/possession-types/{possessionType}/groups/{group}/position',
            'examplePath' => "/possession-types/{$typeId}/groups/{$groupId}/position",
            'description' => 'Move a group to another place among the groups of its type. Every group is numbered again from 1.',
            'rateLimit' => $rateLimit,
            'pathParams' => [$typeParam, $groupParam],
            'bodyParams' => $positionBody,
            'returns' => 'The possession type object as it now reads.',
            'response' => ['data' => $officeEquipment],
        ],
        [
            'id' => 'possession-fields-create',
            'returnFields' => $one,
            'title' => 'Add a field',
            'method' => 'POST',
            'path' => '/possession-types/{possessionType}/fields',
            'examplePath' => "/possession-types/{$typeId}/fields",
            'description' => 'Add a field to a possession type, in one of its groups or standing alone, placed last.',
            'rateLimit' => $rateLimit,
            'pathParams' => [$typeParam],
            'bodyParams' => [
                ...$fieldBody,
                ['name' => 'group_id', 'type' => 'uuid', 'required' => false, 'description' => 'The group it goes in. Left out, the field stands alone. A group of another type is refused. The group cannot be changed later.'],
            ],
            'returns' => 'The possession type object, with the new field.',
            'responseStatus' => 201,
            'response' => ['data' => $officeEquipment],
        ],
        [
            'id' => 'possession-fields-update',
            'returnFields' => $one,
            'title' => 'Change a field',
            'method' => 'PUT',
            'path' => '/possession-types/{possessionType}/fields/{field}',
            'examplePath' => "/possession-types/{$typeId}/fields/{$conditionId}",
            'description' => 'Rename a field, change its kind and set its choices.',
            'body' => ['The body describes the whole field. Options left out are cleared, and a field that stops being a select field loses its choices. A choice removed from a select field is also removed from every possession that held it, and those possessions then hold no value for the field. The deletion is final.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$typeParam, $fieldParam],
            'bodyParams' => $fieldBody,
            'returns' => 'The possession type object as it now reads.',
            'response' => ['data' => $officeEquipment],
        ],
        [
            'id' => 'possession-fields-destroy',
            'title' => 'Remove a field',
            'method' => 'DELETE',
            'path' => '/possession-types/{possessionType}/fields/{field}',
            'examplePath' => "/possession-types/{$typeId}/fields/{$serialId}",
            'description' => 'Remove a field from its possession type.',
            'rateLimit' => $rateLimit,
            'pathParams' => [$typeParam, $fieldParam],
            'returns' => 'Nothing.',
            'responseStatus' => 204,
            'response' => [],
        ],
        [
            'id' => 'possession-fields-position',
            'returnFields' => $one,
            'title' => 'Move a field',
            'method' => 'PUT',
            'path' => '/possession-types/{possessionType}/fields/{field}/position',
            'examplePath' => "/possession-types/{$typeId}/fields/{$conditionId}/position",
            'description' => 'Move a field to another place among its siblings: the fields of its group, or the standalone fields of the type.',
            'rateLimit' => $rateLimit,
            'pathParams' => [$typeParam, $fieldParam],
            'bodyParams' => $positionBody,
            'returns' => 'The possession type object as it now reads.',
            'response' => ['data' => $officeEquipment],
        ],
    ],
];
