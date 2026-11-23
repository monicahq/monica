<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();

$exportId = 'e7a1d9c4-3b6f-4f2a-8d15-6c0b9e2a4f73';
$scrantonId = '9b2d4f6a-1e3c-4a5b-8c7d-0f1e2d3c4b5a';
$stamfordId = 'c4e6a8b0-2d4f-4a6b-9c8d-1e3f5a7b9c0d';

$ready = [
    'type' => 'account_export',
    'id' => $exportId,
    'attributes' => [
        'status' => 'ready',
        'vaults' => [$scrantonId, $stamfordId],
        'size_in_bytes' => 18734522,
        'ready_at' => 1792310700,
        'expires_at' => 1792915500,
        'created_at' => 1792310400,
    ],
    'links' => [
        'self' => $base.'/exports/'.$exportId,
    ],
];

$preparing = array_replace_recursive($ready, ['attributes' => [
    'status' => 'preparing',
    'vaults' => [$scrantonId],
    'size_in_bytes' => null,
    'ready_at' => null,
    'expires_at' => null,
]]);

$exportIdentifier = [
    ['name' => 'accountExport', 'type' => 'uuid', 'required' => true, 'description' => 'The id of the export. One belonging to another account answers 404.'],
];

$exportAttributes = [
    ['name' => 'status', 'type' => 'string', 'description' => 'Where the export stands: preparing, ready, failed or expired. The file is prepared in the background, and an email says when it is ready.'],
    ['name' => 'vaults', 'type' => 'array', 'description' => 'The vaults the file holds.'],
    ['name' => 'vaults[]', 'type' => 'uuid', 'description' => 'The id of one of those vaults. A vault deleted since the export was asked for is left out.'],
    ['name' => 'size_in_bytes', 'type' => 'integer', 'description' => 'The size of the file in bytes, or null until it is ready.'],
    ['name' => 'ready_at', 'type' => 'timestamp', 'description' => 'When the file was complete, as a count of seconds since 1970, or null until it is ready.'],
    ['name' => 'expires_at', 'type' => 'timestamp', 'description' => 'When the file is deleted, seven days after it was ready unless a newer export of the account is ready first, as a count of seconds since 1970, or null until it is ready. From that moment it can no longer be downloaded.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the export was asked for, as a count of seconds since 1970.'],
];

$pageParams = [
    ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Which page to answer with.', 'default' => '1', 'example' => 2],
    ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'How many records one page holds, between 1 and 100. A number outside the range is brought back into it rather than refused.', 'default' => '25', 'example' => 10],
];

return [
    'name' => 'Exports',
    'description' => 'Take the data of the account out, as one JSON file another instance of this application can import. The file is prepared in the background and kept for seven days, or until a newer export of the account is ready, then deleted.',
    'sections' => [
        [
            'id' => 'account-exports-index',
            'returnFields' => ApiDocsFields::page('account_export', 'export', 'exports', $exportAttributes),
            'title' => 'List exports',
            'label' => 'List exports',
            'method' => 'GET',
            'path' => '/exports',
            'description' => 'List the exports of the account, the newest first, including the ones that failed or expired.',
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'queryParams' => $pageParams,
            'returns' => 'A page of export objects, the newest first.',
            'response' => [
                'data' => [$ready],
                'meta' => ['page' => 1, 'per_page' => 25, 'total' => 1],
            ],
        ],
        [
            'id' => 'account-exports-show',
            'returnFields' => ApiDocsFields::one('account_export', 'export', $exportAttributes),
            'title' => 'Get an export',
            'label' => 'Get an export',
            'method' => 'GET',
            'path' => '/exports/{accountExport}',
            'examplePath' => '/exports/'.$exportId,
            'description' => 'Retrieve one export, to follow it until its file is ready.',
            'body' => [
                'An export moves on its own, in the background, while its status is preparing. Ask again to follow it. An email says when it is ready.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $exportIdentifier,
            'returns' => 'An export object. One of another account answers 404.',
            'response' => ['data' => $ready],
        ],
        [
            'id' => 'account-exports-create',
            'returnFields' => ApiDocsFields::one('account_export', 'export', $exportAttributes),
            'title' => 'Export the account',
            'label' => 'Export the account',
            'method' => 'POST',
            'path' => '/exports',
            'description' => 'Ask for a file holding everything in the chosen vaults, or in every vault of the account.',
            'body' => [
                'The file holds every contact, value, relationship, life event, reminder and file of those vaults, sensitive fields included, and it is not encrypted. Only a key holding the sensitive fields scope can ask for one: any other key answers 403. A key minted for everything does not count, the scope has to be asked for by name.',
                'The file is prepared in the background. Ask for the export until its status is ready, then download it with GET /exports/{accountExport}/download.',
                'An account prepares one export at a time. Asking for another while one is being prepared answers 422.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'bodyParams' => [
                ['name' => 'vaults', 'type' => 'array', 'required' => false, 'description' => 'The vaults to put in the file, each named by its slug or its id. Leave it out to export every vault of the account. An empty list answers 422, and a vault of another account answers 404.', 'example' => [$scrantonId]],
            ],
            'returns' => 'The export object, being prepared.',
            'responseStatus' => 202,
            'response' => ['data' => $preparing],
        ],
        [
            'id' => 'account-exports-download',
            'title' => 'Download an export',
            'label' => 'Download an export',
            'method' => 'GET',
            'path' => '/exports/{accountExport}/download',
            'examplePath' => '/exports/'.$exportId.'/download',
            'description' => 'Retrieve the file of an export that is ready.',
            'body' => [
                'The body is the file itself, one JSON document with a file name attached, so what you receive can be written straight to disk. It is not wrapped in data like the other answers here. Its top level holds version (2.0-preview.1), app_version, exported_at and account, and account holds the vaults with everything in them.',
                'Only a key holding the sensitive fields scope can download the file, since it holds every sensitive value: any other key answers 403.',
                'An export that is not ready, or whose expires_at has passed, answers 404. Each download is written to the log of the account.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $exportIdentifier,
            'returns' => 'The file, as JSON. An export of another account answers 404.',
            'responseIsFile' => true,
        ],
    ],
];
