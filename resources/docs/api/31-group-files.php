<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();

$vaultSlug = 'dunder-mifflin';
$groupId = '5d2a7c91-3e48-4b06-9f1d-a83c60e2b4f7';
$fileId = 'c4a8e1d7-6b32-4f95-8d10-2e7a93b5f6c8';

$groupFile = [
    'type' => 'group_file',
    'id' => $fileId,
    'attributes' => [
        'group' => [
            'id' => $groupId,
            'name' => 'Party planning committee',
        ],
        'name' => 'office-picnic-schedule.pdf',
        'mime_type' => 'application/pdf',
        'size_in_bytes' => 48213,
        'created_at' => 1791547200,
    ],
    'links' => [
        'self' => $base.'/vaults/'.$vaultSlug.'/groups/'.$groupId.'/files/'.$fileId,
        'content' => $base.'/vaults/'.$vaultSlug.'/groups/'.$groupId.'/files/'.$fileId.'/content',
    ],
];

$flagNote = 'Files kept with a group need both groups and file upload to be available to your account. Until they are, every endpoint here answers 404.';

$groupIdentifier = [
    ['name' => 'vault', 'type' => 'string', 'required' => true, 'description' => 'The vault the group lives in, named either by the readable name it reports as slug or by its id.'],
    ['name' => 'group', 'type' => 'uuid', 'required' => true, 'description' => 'The id of the group. One addressed through the wrong vault, deleted, or belonging to another account, answers 404.'],
];

$fileIdentifier = [
    ...$groupIdentifier,
    ['name' => 'file', 'type' => 'uuid', 'required' => true, 'description' => 'The id of the file as kept with the group, which is the id of the group file object and not the id of the stored file. One kept with another group answers 404.'],
];

$fileAttributes = [
    ['name' => 'group', 'type' => 'object', 'description' => 'The group the file is kept with.'],
    ['name' => 'group.id', 'type' => 'uuid', 'description' => 'The id of the group.'],
    ['name' => 'group.name', 'type' => 'string', 'description' => 'What the group is called.'],
    ['name' => 'name', 'type' => 'string', 'description' => 'The name the file had when it was uploaded.'],
    ['name' => 'mime_type', 'type' => 'string', 'description' => 'The type of the file, such as application/pdf.'],
    ['name' => 'size_in_bytes', 'type' => 'integer', 'description' => 'How large the file is, in bytes. It counts against the storage of the account.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the file was kept with the group, as a count of seconds since 1970.'],
];

$contentLink = fn (string $prefix): array => [
    'name' => $prefix.'.links.content',
    'type' => 'string',
    'description' => 'The absolute address the file itself is downloaded from.',
];

return [
    'name' => 'Group files',
    'description' => 'The files kept with a group as a whole: a team photo, a schedule, a registration form. A file kept with a group counts against the storage of the account like every other upload.',
    'sections' => [
        [
            'id' => 'group-files-index',
            'flag' => ['groups', 'file-upload'],
            'returnFields' => [...ApiDocsFields::page('group_file', 'file', 'files', $fileAttributes), $contentLink('data[]')],
            'title' => 'List the files of a group',
            'label' => 'List files',
            'method' => 'GET',
            'path' => '/vaults/{vault}/groups/{group}/files',
            'examplePath' => '/vaults/'.$vaultSlug.'/groups/'.$groupId.'/files',
            'description' => 'List the files kept with a group, the newest first.',
            'body' => [$flagNote],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $groupIdentifier,
            'queryParams' => [
                ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Which page to answer with.', 'default' => '1'],
                ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'How many records one page holds, between 1 and 100. A number outside the range is brought back into it rather than refused.', 'default' => '25'],
            ],
            'returns' => 'A page of group file objects, and how many there are in all. A group belonging to somebody else answers 404.',
            'response' => [
                'data' => [$groupFile],
                'meta' => ['page' => 1, 'per_page' => 25, 'total' => 1],
            ],
        ],
        [
            'id' => 'group-files-show',
            'flag' => ['groups', 'file-upload'],
            'returnFields' => [...ApiDocsFields::one('group_file', 'file', $fileAttributes), $contentLink('data')],
            'title' => 'Get a file of a group',
            'label' => 'Get a file',
            'method' => 'GET',
            'path' => '/vaults/{vault}/groups/{group}/files/{file}',
            'examplePath' => '/vaults/'.$vaultSlug.'/groups/'.$groupId.'/files/'.$fileId,
            'description' => 'Return the name, type and size of one file kept with a group. The content is a request of its own, at the address in links.content.',
            'body' => [$flagNote],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $fileIdentifier,
            'returns' => 'The group file object. A file of another account, or one that is no longer kept with the group, answers 404.',
            'response' => ['data' => $groupFile],
        ],
        [
            'id' => 'group-files-create',
            'flag' => ['groups', 'file-upload'],
            'returnFields' => [...ApiDocsFields::one('group_file', 'file', $fileAttributes), $contentLink('data')],
            'title' => 'Keep a file with a group',
            'label' => 'Keep a file',
            'method' => 'POST',
            'path' => '/vaults/{vault}/groups/{group}/files',
            'examplePath' => '/vaults/'.$vaultSlug.'/groups/'.$groupId.'/files',
            'description' => 'Upload a file and keep it with a group. The change is written to the account log.',
            'body' => [
                $flagNote,
                'Send the file as multipart form data, in a field called file. The type of the file is not restricted.',
                'An upload larger than the size this instance allows answers 422 on file, and so does an upload that would take the account past the storage it has left. In both cases nothing is kept.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $groupIdentifier,
            'bodyParams' => [
                ['name' => 'file', 'type' => 'file', 'required' => true, 'description' => 'The file to keep with the group. A request without one answers 422 on file.'],
            ],
            'returns' => 'The group file object that was written.',
            'responseStatus' => 201,
            'response' => ['data' => $groupFile],
        ],
        [
            'id' => 'group-files-destroy',
            'flag' => ['groups', 'file-upload'],
            'title' => 'Stop keeping a file with a group',
            'label' => 'Remove a file',
            'method' => 'DELETE',
            'path' => '/vaults/{vault}/groups/{group}/files/{file}',
            'examplePath' => '/vaults/'.$vaultSlug.'/groups/'.$groupId.'/files/'.$fileId,
            'description' => 'Stop keeping a file with a group. The change is written to the account log.',
            'body' => [
                $flagNote,
                'The file is let go rather than deleted outright. It stays wherever else it is used, and is swept up after a grace period once nothing uses it.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $fileIdentifier,
            'returns' => 'Nothing at all. A file of another account, or one kept with another group, answers 404.',
            'responseStatus' => 204,
        ],
        [
            'id' => 'group-files-content-show',
            'flag' => ['groups', 'file-upload'],
            'title' => 'Download a file of a group',
            'label' => 'Download a file',
            'method' => 'GET',
            'path' => '/vaults/{vault}/groups/{group}/files/{file}/content',
            'examplePath' => '/vaults/'.$vaultSlug.'/groups/'.$groupId.'/files/'.$fileId.'/content',
            'description' => 'Return the file itself, byte for byte.',
            'body' => [
                $flagNote,
                'This endpoint does not answer in JSON. The body is the file, sent with the type and the name it was uploaded with, so what you receive can be written straight to disk. Images are marked to display in place and every other type is marked as a download.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $fileIdentifier,
            'returns' => 'The file. A file of another account, one kept with another group, or one whose content is no longer stored answers 404.',
            'responseMediaType' => 'application/octet-stream',
            'responseIsFile' => true,
        ],
    ],
];
