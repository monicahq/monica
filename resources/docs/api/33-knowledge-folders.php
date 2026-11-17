<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();
$application = rtrim((string) config('app.url'), '/');

$rateLimit = 'Sixty requests per minute, per user, shared by every key and device.';

$vaultSlug = 'dunder-mifflin';
$officeId = '2b7e4c91-0d5a-4f38-9c16-8e3a7f1b5d24';
$warehouseId = 'c6f13a58-4e92-4b07-a1d3-5f8e2c9b7a60';
$speechId = '7a2d9e4f-1c63-4b85-9f07-3e6c8a1d5b92';
$clientsId = 'e94b1c37-6a28-4d50-8b3f-2c7e9a4d1f68';

$webLink = 'The Knowledge screen of the vault in the application, which opens for somebody signed in rather than for a key.';

$office = [
    'type' => 'knowledge_folder',
    'id' => $officeId,
    'attributes' => [
        'name' => 'Office',
        'position' => 1,
        'note_count' => 1,
        'list_count' => 1,
        'notes' => [['id' => $speechId, 'title' => 'Dundies speech']],
        'lists' => [['id' => $clientsId, 'title' => 'Paper clients']],
        'created_at' => 1773331200,
        'updated_at' => 1773331200,
    ],
    'links' => [
        'web' => "{$application}/vaults/{$vaultSlug}/knowledge",
    ],
];

$warehouse = [
    'type' => 'knowledge_folder',
    'id' => $warehouseId,
    'attributes' => [
        'name' => 'Warehouse',
        'position' => 2,
        'note_count' => 0,
        'list_count' => 0,
        'notes' => [],
        'lists' => [],
        'created_at' => 1773417600,
        'updated_at' => 1773417600,
    ],
    'links' => [
        'web' => "{$application}/vaults/{$vaultSlug}/knowledge",
    ],
];

$attributes = [
    ['name' => 'name', 'type' => 'string', 'description' => 'What the folder is called.'],
    ['name' => 'position', 'type' => 'integer', 'description' => 'Where the folder stands among the folders of the vault, from 1.'],
    ['name' => 'note_count', 'type' => 'integer', 'description' => 'How many notes the folder holds.'],
    ['name' => 'list_count', 'type' => 'integer', 'description' => 'How many lists the folder holds.'],
    ['name' => 'notes', 'type' => 'array', 'description' => 'The notes the folder holds.'],
    ['name' => 'notes[]', 'type' => 'object', 'description' => 'One note.'],
    ['name' => 'notes[].id', 'type' => 'uuid', 'description' => 'The id of the note, which the notes endpoints take.'],
    ['name' => 'notes[].title', 'type' => 'string', 'description' => 'The title of the note.'],
    ['name' => 'lists', 'type' => 'array', 'description' => 'The lists the folder holds.'],
    ['name' => 'lists[]', 'type' => 'object', 'description' => 'One list.'],
    ['name' => 'lists[].id', 'type' => 'uuid', 'description' => 'The id of the list, which the lists endpoints take.'],
    ['name' => 'lists[].title', 'type' => 'string', 'description' => 'The title of the list.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the folder was added, as a count of seconds since 1970.'],
    ['name' => 'updated_at', 'type' => 'timestamp', 'description' => 'When it last changed, as a count of seconds, or null.'],
];

$record = function (string $prefix, bool $empty = false) use ($attributes, $webLink): array {
    $rows = [
        ['name' => "{$prefix}.type", 'type' => 'string', 'description' => 'Always knowledge_folder.'],
        ['name' => "{$prefix}.id", 'type' => 'string', 'description' => 'The id of the folder, which every address naming it takes.'],
        ['name' => "{$prefix}.attributes", 'type' => 'object', 'description' => 'Everything the folder holds.'],
    ];

    foreach ($attributes as $attribute) {
        if ($empty && str_contains($attribute['name'], '[]')) {
            continue;
        }

        $rows[] = [...$attribute, 'name' => "{$prefix}.attributes.{$attribute['name']}"];
    }

    $rows[] = ['name' => "{$prefix}.links", 'type' => 'object', 'description' => 'Where to find the folder. A folder is not read on its own, so it has no address of its own.'];
    $rows[] = ['name' => "{$prefix}.links.web", 'type' => 'string', 'description' => $webLink];

    return $rows;
};

$one = [['name' => 'data', 'type' => 'object', 'description' => 'The knowledge folder.'], ...$record('data')];
$new = [['name' => 'data', 'type' => 'object', 'description' => 'The knowledge folder, which holds nothing yet, so its notes and lists are empty.'], ...$record('data', empty: true)];

$vaultParam = ['name' => 'vault', 'type' => 'string', 'required' => true, 'description' => 'The vault, named either by the readable name it reports as slug or by its id. A vault of another account answers 404.'];
$folderParam = ['name' => 'folder', 'type' => 'uuid', 'required' => true, 'description' => 'The id of the folder. A folder of another vault or of another account answers 404.'];

return [
    'name' => 'Knowledge folders',
    'description' => 'The folders of a vault\'s Knowledge, which group its notes and lists by theme: Home, Travel, Health. Folders are flat, never nested, and a vault that holds Knowledge keeps at least one.',
    'sections' => [
        [
            'id' => 'knowledge-folders-index',
            'returnFields' => [
                ['name' => 'data', 'type' => 'array', 'description' => 'Every folder of the vault, whole rather than a page at a time.'],
                ['name' => 'data[]', 'type' => 'object', 'description' => 'One knowledge folder.'],
                ...$record('data[]'),
            ],
            'title' => 'List the Knowledge of a vault',
            'label' => 'List folders',
            'method' => 'GET',
            'path' => '/vaults/{vault}/knowledge/folders',
            'examplePath' => "/vaults/{$vaultSlug}/knowledge/folders",
            'description' => 'List every folder of a vault\'s Knowledge in its order, each with the ids and titles of its notes and lists.',
            'body' => ['It is the table of contents of the vault\'s Knowledge. Read a note or a list through its own endpoint.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam],
            'returns' => 'Every knowledge folder object of the vault, or an empty list. A vault of another account answers 404.',
            'response' => ['data' => [$office, $warehouse]],
        ],
        [
            'id' => 'knowledge-folders-create',
            'returnFields' => $new,
            'title' => 'Add a folder',
            'method' => 'POST',
            'path' => '/vaults/{vault}/knowledge/folders',
            'examplePath' => "/vaults/{$vaultSlug}/knowledge/folders",
            'description' => 'Add a folder to a vault\'s Knowledge, after the folders already there.',
            'body' => ['The change is written to the account log.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam],
            'bodyParams' => [
                ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => 'What the folder is called, up to 255 characters. Markup is stripped, and a name left empty once it is stripped is refused with “Give the folder a name.”', 'example' => 'Warehouse'],
            ],
            'returns' => 'The knowledge folder object that was added.',
            'responseStatus' => 201,
            'response' => ['data' => $warehouse],
        ],
        [
            'id' => 'knowledge-folders-update',
            'returnFields' => $one,
            'title' => 'Rename or move a folder',
            'label' => 'Change a folder',
            'method' => 'PUT',
            'path' => '/vaults/{vault}/knowledge/folders/{folder}',
            'examplePath' => "/vaults/{$vaultSlug}/knowledge/folders/{$officeId}",
            'description' => 'Rename a folder, move it among the folders of the vault, or both.',
            'body' => [
                'A field left out keeps its value, and a name left empty once markup is stripped keeps the one the folder had. The other folders shift so the positions run from 1 without a gap.',
                'The change is written to the account log.',
            ],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $folderParam],
            'bodyParams' => [
                ['name' => 'name', 'type' => 'string', 'required' => false, 'description' => 'The new name, up to 255 characters.', 'example' => 'Office'],
                ['name' => 'position', 'type' => 'integer', 'required' => false, 'description' => 'Where the folder goes, 1 for first. A number past the end puts it last.', 'example' => 1],
            ],
            'returns' => 'The knowledge folder object as it now reads. A folder of another vault or of another account answers 404.',
            'response' => ['data' => $office],
        ],
        [
            'id' => 'knowledge-folders-destroy',
            'title' => 'Delete a folder',
            'method' => 'DELETE',
            'path' => '/vaults/{vault}/knowledge/folders/{folder}',
            'examplePath' => "/vaults/{$vaultSlug}/knowledge/folders/{$warehouseId}",
            'description' => 'Delete a folder of a vault\'s Knowledge, moving its notes and lists to another folder or deleting them with it.',
            'body' => [
                'Name another folder of the same vault as move_to to keep the notes and lists. Without it they are deleted for good with the folder.',
                'The last folder of a vault cannot be deleted, and is refused with 422 and “The last folder cannot be deleted. Knowledge needs at least one.”',
                'The change is written to the account log.',
            ],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $folderParam],
            'bodyParams' => [
                ['name' => 'move_to', 'type' => 'uuid', 'required' => false, 'description' => 'The id of another folder of the vault, to move the notes and lists to. A folder of another vault answers 404, and the folder being deleted answers 422.', 'example' => $officeId],
            ],
            'returns' => 'Nothing. A folder of another vault or of another account answers 404.',
            'responseStatus' => 204,
            'response' => [],
        ],
    ],
];
