<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();
$application = rtrim((string) config('app.url'), '/');

$rateLimit = 'Sixty requests per minute, per user, shared by every key and device.';

$vaultSlug = 'dunder-mifflin';
$officeId = '2b7e4c91-0d5a-4f38-9c16-8e3a7f1b5d24';
$clientsId = 'e94b1c37-6a28-4d50-8b3f-2c7e9a4d1f68';
$nameId = '5d3a8f12-7c49-4e60-b2a1-9f6e3c8d4b75';
$cityId = 'a18c6e43-2f97-4b5d-8e01-7c3b9a6f2d84';
$noteId = '0f7b2d95-3e61-4a8c-9d47-6b1e8c5a3f29';
$blueCrossId = '8e4f1a76-5c23-4d9b-a0e8-2b7d6f3c1e54';
$harperId = '3c9d7b28-1f54-4e8a-b6c0-5a2e9d4f7b13';

$webLink = 'The list in the application, which opens for somebody signed in rather than for a key. It is the link to give a person.';

$base_ = [
    'title' => 'Paper clients',
    'description' => 'Who buys from the **Scranton** branch.',
    'folder' => ['id' => $officeId, 'name' => 'Office'],
];

$links = [
    'self' => "{$base}/vaults/{$vaultSlug}/knowledge/lists/{$clientsId}",
    'web' => "{$application}/vaults/{$vaultSlug}/knowledge/lists/{$clientsId}",
];

$clients = [
    'type' => 'knowledge_list',
    'id' => $clientsId,
    'attributes' => [
        ...$base_,
        'columns' => [
            ['id' => $nameId, 'name' => 'Name', 'position' => 1],
            ['id' => $cityId, 'name' => 'City', 'position' => 2],
            ['id' => $noteId, 'name' => 'Note', 'position' => 3],
        ],
        'items' => [
            ['id' => $blueCrossId, 'position' => 1, 'cells' => [$nameId => 'Blue Cross of Pennsylvania', $cityId => 'Scranton']],
            ['id' => $harperId, 'position' => 2, 'cells' => [$nameId => 'Harper Collins', $noteId => 'Lost to Prince Family Paper, won back']],
        ],
        'created_at' => 1773331200,
        'updated_at' => 1773417600,
    ],
    'links' => $links,
];

$listed = [
    'type' => 'knowledge_list',
    'id' => $clientsId,
    'attributes' => [...$base_, 'created_at' => 1773331200, 'updated_at' => 1773417600],
    'links' => $links,
];

$shared = [
    ['name' => 'title', 'type' => 'string', 'description' => 'The title of the list.'],
    ['name' => 'description', 'type' => 'string', 'description' => 'What the list is for, in Markdown as written, or null.'],
    ['name' => 'folder', 'type' => 'object', 'description' => 'The folder the list sits in.'],
    ['name' => 'folder.id', 'type' => 'uuid', 'description' => 'The identifier of the folder, as the knowledge folders endpoints give it.'],
    ['name' => 'folder.name', 'type' => 'string', 'description' => 'What the folder is called.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the list was started, as a count of seconds since 1970.'],
    ['name' => 'updated_at', 'type' => 'timestamp', 'description' => 'When it, one of its columns or one of its items last changed, as a count of seconds, or null.'],
];

$attributes = [
    ...$shared,
    ['name' => 'columns', 'type' => 'array', 'description' => 'The columns of the list, in their order. A list always has at least one.'],
    ['name' => 'columns[]', 'type' => 'object', 'description' => 'One column.'],
    ['name' => 'columns[].id', 'type' => 'uuid', 'description' => 'The identifier of the column, which the columns endpoints and the cells of an item take.'],
    ['name' => 'columns[].name', 'type' => 'string', 'description' => 'What the column is called.'],
    ['name' => 'columns[].position', 'type' => 'integer', 'description' => 'Where the column stands, from 1.'],
    ['name' => 'items', 'type' => 'array', 'description' => 'The items of the list, in their order.'],
    ['name' => 'items[]', 'type' => 'object', 'description' => 'One item.'],
    ['name' => 'items[].id', 'type' => 'uuid', 'description' => 'The identifier of the item, which the items endpoints take.'],
    ['name' => 'items[].position', 'type' => 'integer', 'description' => 'Where the item stands, from 1.'],
    ['name' => 'items[].cells', 'type' => 'object', 'description' => 'The texts of the item in Markdown, keyed by the identifier of their column. A blank cell is left out.', 'opaque' => true],
];

$one = ApiDocsFields::one('knowledge_list', 'knowledge list', $attributes, webLink: $webLink);
$started = ApiDocsFields::one('knowledge_list', 'knowledge list, which holds no item yet', array_values(array_filter($attributes, fn (array $attribute): bool => ! str_starts_with($attribute['name'], 'items['))), webLink: $webLink);

$vaultParam = ['name' => 'vault', 'type' => 'string', 'required' => true, 'description' => 'The vault, named either by the readable name it reports as slug or by the identifier it reports as id. A vault of another account answers 404.'];
$listParam = ['name' => 'list', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of the list. A list of another vault or of another account answers 404.'];
$columnParam = ['name' => 'column', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of the column. A column of another list answers 404.'];
$itemParam = ['name' => 'item', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of the item. An item of another list answers 404.'];
$folderParam = 'The identifier of a folder of the vault, as the knowledge folders endpoints give it. A folder of another vault answers 404.';
$descriptionParam = 'What the list is for, in Markdown, up to 5,000 characters.';
$cellsParam = 'The texts of the item, keyed by the identifier of their column, each in Markdown and up to 5,000 characters. A key naming no column of the list answers 422.';
$log = 'The change is written to the account log.';

return [
    'name' => 'Knowledge lists',
    'description' => 'Tables kept in a folder of a vault\'s Knowledge: the books somebody has read, the paint reference of each room. The owner names the columns, every column holds text, and each item holds one text per column.',
    'sections' => [
        [
            'id' => 'knowledge-lists-index',
            'returnFields' => [
                ...ApiDocsFields::all('knowledge_list', 'knowledge list, without its columns and items', 'lists', $shared),
                ['name' => 'data[].links.web', 'type' => 'string', 'description' => $webLink],
            ],
            'title' => 'List the lists of a vault',
            'label' => 'List lists',
            'method' => 'GET',
            'path' => '/vaults/{vault}/knowledge/lists',
            'examplePath' => "/vaults/{$vaultSlug}/knowledge/lists",
            'description' => 'List the lists of a vault\'s Knowledge by title, without their columns and items.',
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam],
            'queryParams' => [
                ['name' => 'folder', 'type' => 'uuid', 'required' => false, 'description' => "Only the lists of this folder. {$folderParam}"],
            ],
            'returns' => 'Every knowledge list object of the vault, without its columns and items, or an empty list. A vault of another account answers 404.',
            'response' => ['data' => [$listed]],
        ],
        [
            'id' => 'knowledge-lists-show',
            'returnFields' => $one,
            'title' => 'Get a list',
            'method' => 'GET',
            'path' => '/vaults/{vault}/knowledge/lists/{list}',
            'examplePath' => "/vaults/{$vaultSlug}/knowledge/lists/{$clientsId}",
            'description' => 'Retrieve one list with its columns and its items.',
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $listParam],
            'returns' => 'A knowledge list object. A list of another vault or of another account answers 404.',
            'response' => ['data' => $clients],
        ],
        [
            'id' => 'knowledge-lists-create',
            'returnFields' => $started,
            'title' => 'Start a list',
            'method' => 'POST',
            'path' => '/vaults/{vault}/knowledge/lists',
            'examplePath' => "/vaults/{$vaultSlug}/knowledge/lists",
            'description' => 'Start a list in a vault\'s Knowledge, with the columns Name and Note.',
            'body' => [
                'Every field is optional. A list without a title is called “Untitled list”. Without a folder it goes to the first folder of the vault, and a vault without any folder gets one called “General” to hold it. Rename, add or remove columns through the columns endpoints.',
                $log,
            ],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam],
            'bodyParams' => [
                ['name' => 'title', 'type' => 'string', 'required' => false, 'description' => 'The title of the list, up to 255 characters. Markup is stripped.', 'example' => 'Paper clients'],
                ['name' => 'description', 'type' => 'string', 'required' => false, 'description' => $descriptionParam, 'example' => 'Who buys from the **Scranton** branch.'],
                ['name' => 'folder_id', 'type' => 'uuid', 'required' => false, 'description' => $folderParam, 'example' => $officeId],
            ],
            'returns' => 'The knowledge list object that was started.',
            'responseStatus' => 201,
            'response' => ['data' => [...$clients, 'attributes' => [...$clients['attributes'], 'columns' => [['id' => $nameId, 'name' => 'Name', 'position' => 1], ['id' => $noteId, 'name' => 'Note', 'position' => 2]], 'items' => []]]],
        ],
        [
            'id' => 'knowledge-lists-update',
            'returnFields' => $one,
            'title' => 'Change a list',
            'method' => 'PUT',
            'path' => '/vaults/{vault}/knowledge/lists/{list}',
            'examplePath' => "/vaults/{$vaultSlug}/knowledge/lists/{$clientsId}",
            'description' => 'Change the title, the description or the folder of a list.',
            'body' => [
                'A field left out keeps its value. A title left empty keeps the one the list had, and a description sent as null or empty clears it.',
                $log,
            ],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $listParam],
            'bodyParams' => [
                ['name' => 'title', 'type' => 'string', 'required' => false, 'description' => 'The new title, up to 255 characters.'],
                ['name' => 'description', 'type' => 'string', 'required' => false, 'description' => $descriptionParam, 'example' => 'Who buys from the **Scranton** branch.'],
                ['name' => 'folder_id', 'type' => 'uuid', 'required' => false, 'description' => "The folder to move the list to. {$folderParam}"],
            ],
            'returns' => 'The knowledge list object as it now reads. A list of another vault or of another account answers 404.',
            'response' => ['data' => $clients],
        ],
        [
            'id' => 'knowledge-lists-destroy',
            'title' => 'Delete a list',
            'method' => 'DELETE',
            'path' => '/vaults/{vault}/knowledge/lists/{list}',
            'examplePath' => "/vaults/{$vaultSlug}/knowledge/lists/{$clientsId}",
            'description' => 'Delete a list with its columns and every item in it.',
            'body' => ['The deletion is final. The account log keeps the title of the list.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $listParam],
            'returns' => 'Nothing. A list of another vault or of another account answers 404.',
            'responseStatus' => 204,
            'response' => [],
        ],
        [
            'id' => 'knowledge-list-columns-create',
            'returnFields' => $one,
            'title' => 'Add a column',
            'method' => 'POST',
            'path' => '/vaults/{vault}/knowledge/lists/{list}/columns',
            'examplePath' => "/vaults/{$vaultSlug}/knowledge/lists/{$clientsId}/columns",
            'description' => 'Add a column to a list, after its other columns. Every item reads it as blank until something is written in it.',
            'body' => [$log],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $listParam],
            'bodyParams' => [
                ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => 'What the column is called, up to 255 characters. A name left empty once markup is stripped is refused with “Give the column a name.”', 'example' => 'City'],
            ],
            'returns' => 'The whole knowledge list object, with its new column.',
            'responseStatus' => 201,
            'response' => ['data' => $clients],
        ],
        [
            'id' => 'knowledge-list-columns-update',
            'returnFields' => $one,
            'title' => 'Rename or move a column',
            'label' => 'Change a column',
            'method' => 'PUT',
            'path' => '/vaults/{vault}/knowledge/lists/{list}/columns/{column}',
            'examplePath' => "/vaults/{$vaultSlug}/knowledge/lists/{$clientsId}/columns/{$cityId}",
            'description' => 'Rename a column, move it among the columns of its list, or both.',
            'body' => ['A field left out keeps its value. The other columns shift so the positions run from 1 without a gap.', $log],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $listParam, $columnParam],
            'bodyParams' => [
                ['name' => 'name', 'type' => 'string', 'required' => false, 'description' => 'The new name, up to 255 characters. A name left empty keeps the one the column had.', 'example' => 'City'],
                ['name' => 'position', 'type' => 'integer', 'required' => false, 'description' => 'Where the column goes, 1 for first. A number past the end puts it last.', 'example' => 2],
            ],
            'returns' => 'The whole knowledge list object as it now reads.',
            'response' => ['data' => $clients],
        ],
        [
            'id' => 'knowledge-list-columns-destroy',
            'title' => 'Remove a column',
            'method' => 'DELETE',
            'path' => '/vaults/{vault}/knowledge/lists/{list}/columns/{column}',
            'examplePath' => "/vaults/{$vaultSlug}/knowledge/lists/{$clientsId}/columns/{$noteId}",
            'description' => 'Remove a column from a list, with what every item holds in it.',
            'body' => [
                'The deletion is final. The last column of a list cannot be removed, and is refused with 422 and “The last column cannot be removed. A list needs at least one.”',
                $log,
            ],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $listParam, $columnParam],
            'returns' => 'Nothing. A column of another list answers 404.',
            'responseStatus' => 204,
            'response' => [],
        ],
        [
            'id' => 'knowledge-list-items-create',
            'returnFields' => $one,
            'title' => 'Add an item',
            'method' => 'POST',
            'path' => '/vaults/{vault}/knowledge/lists/{list}/items',
            'examplePath' => "/vaults/{$vaultSlug}/knowledge/lists/{$clientsId}/items",
            'description' => 'Add an item to a list, after its other items.',
            'body' => [
                'An item whose texts are all blank is refused with “Write something in at least one column.”',
                $log,
            ],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $listParam],
            'bodyParams' => [
                ['name' => 'cells', 'type' => 'object', 'required' => true, 'description' => $cellsParam, 'example' => [$nameId => 'Harper Collins', $noteId => 'Lost to Prince Family Paper, won back']],
            ],
            'returns' => 'The whole knowledge list object, with its new item.',
            'responseStatus' => 201,
            'response' => ['data' => $clients],
        ],
        [
            'id' => 'knowledge-list-items-update',
            'returnFields' => $one,
            'title' => 'Change an item',
            'method' => 'PUT',
            'path' => '/vaults/{vault}/knowledge/lists/{list}/items/{item}',
            'examplePath' => "/vaults/{$vaultSlug}/knowledge/lists/{$clientsId}/items/{$blueCrossId}",
            'description' => 'Change the texts of an item.',
            'body' => [
                'Only the columns sent change, and a text sent empty empties its cell. An item left with every text blank is refused with “Write something in at least one column.”',
                $log,
            ],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $listParam, $itemParam],
            'bodyParams' => [
                ['name' => 'cells', 'type' => 'object', 'required' => true, 'description' => $cellsParam, 'example' => [$cityId => 'Scranton']],
            ],
            'returns' => 'The whole knowledge list object as it now reads.',
            'response' => ['data' => $clients],
        ],
        [
            'id' => 'knowledge-list-items-destroy',
            'title' => 'Delete an item',
            'method' => 'DELETE',
            'path' => '/vaults/{vault}/knowledge/lists/{list}/items/{item}',
            'examplePath' => "/vaults/{$vaultSlug}/knowledge/lists/{$clientsId}/items/{$harperId}",
            'description' => 'Delete an item of a list, with everything written in it.',
            'body' => ['The deletion is final.', $log],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $listParam, $itemParam],
            'returns' => 'Nothing. An item of another list answers 404.',
            'responseStatus' => 204,
            'response' => [],
        ],
    ],
];
