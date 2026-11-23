<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();

$importId = '3f8a1c52-7d4e-4b19-9a06-c2e5b8d71f40';
$scrantonId = '9b2d4f6a-1e3c-4a5b-8c7d-0f1e2d3c4b5a';
$stamfordId = 'c4e6a8b0-2d4f-4a6b-9c8d-1e3f5a7b9c0d';
$michaelInTheFile = '10000000-0000-4000-8000-000000000001';

$completed = [
    'type' => 'account_import',
    'id' => $importId,
    'attributes' => [
        'status' => 'completed',
        'source_format' => 'monica_4',
        'refusal' => null,
        'summary' => [
            ['kind' => 'contacts', 'found' => 1767, 'left_out' => 0],
            ['kind' => 'relationships', 'found' => 94, 'left_out' => 0],
            ['kind' => 'audit_logs', 'found' => 3327, 'left_out' => 3327],
        ],
        'already_imported_into' => [$scrantonId],
        'progress' => ['done' => 4512, 'total' => 4512],
        'vault' => $stamfordId,
        'me_contact' => $michaelInTheFile,
        'report' => [
            ['kind' => 'contact', 'imported' => 1767, 'left_out' => 0],
            ['kind' => 'reminder', 'imported' => 112, 'left_out' => 6],
            ['kind' => 'relationship', 'imported' => 47, 'left_out' => 47],
            ['kind' => 'audit_logs', 'imported' => 0, 'left_out' => 3327],
        ],
        'created_at' => 1792310400,
        'ended_at' => 1792310940,
    ],
    'links' => [
        'self' => $base.'/imports/'.$importId,
    ],
];

$reading = array_replace_recursive($completed, ['attributes' => [
    'status' => 'reading',
    'source_format' => null,
    'summary' => null,
    'progress' => ['done' => 0, 'total' => 0],
    'vault' => null,
    'me_contact' => null,
    'report' => null,
    'ended_at' => null,
]]);

$writing = array_replace_recursive($completed, ['attributes' => [
    'status' => 'writing',
    'progress' => ['done' => 0],
    'report' => null,
    'ended_at' => null,
]]);

$rollingBack = array_replace_recursive($writing, ['attributes' => [
    'status' => 'rolling_back',
    'progress' => ['done' => 1200],
]]);

$importIdentifier = [
    ['name' => 'accountImport', 'type' => 'uuid', 'required' => true, 'description' => 'The id of the import. One belonging to another account answers 404.'],
];

$importAttributes = [
    ['name' => 'status', 'type' => 'string', 'description' => 'Where the import stands: reading, refused, read, writing, rolling_back, rolled_back, rollback_failed or completed. rollback_failed means an undo kept failing and what the import wrote is still in place. Nothing is written into a vault before the file is read and you have chosen where it goes.'],
    ['name' => 'source_format', 'type' => 'string', 'description' => 'What the file is: monica_4 for an export of Monica 4, monica for an export of this application. Null while the file is read, and when it was refused as neither.'],
    ['name' => 'refusal', 'type' => 'string', 'description' => 'Why the file cannot be imported, when the status is refused. Null otherwise.'],
    ['name' => 'already_imported_into', 'type' => 'array', 'description' => 'The vaults this same file was completely imported into before. An import only adds, so importing it again into one of them writes every record a second time.'],
    ['name' => 'already_imported_into[]', 'type' => 'uuid', 'description' => 'The id of one of those vaults.'],
    ['name' => 'progress', 'type' => 'object', 'description' => 'How far the writing has gone.'],
    ['name' => 'progress.done', 'type' => 'integer', 'description' => 'How many records are written.'],
    ['name' => 'progress.total', 'type' => 'integer', 'description' => 'How many records there are to write, known once the file is read.'],
    ['name' => 'vault', 'type' => 'uuid', 'description' => 'The id of the vault the file is written into, or null until one is chosen.'],
    ['name' => 'me_contact', 'type' => 'string', 'description' => 'The id, in the file, of the contact who is you, or null for none. Until you choose, it is the contact the file names as its owner.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the file was received, as a count of seconds since 1970.'],
    ['name' => 'ended_at', 'type' => 'timestamp', 'description' => 'When the import completed or finished being undone, as a count of seconds since 1970, or null.'],
];

$summary = ['name' => 'summary', 'type' => 'array', 'description' => 'What the file holds, one entry per kind of record, once it is read. Null while it is read.'];

$summaryEntries = [
    ['name' => 'summary[]', 'type' => 'object', 'description' => 'One kind of record in the file.'],
    ['name' => 'summary[].kind', 'type' => 'string', 'description' => 'The kind of record, as the file names it: contacts, notes, relationships, audit_logs and so on.'],
    ['name' => 'summary[].found', 'type' => 'integer', 'description' => 'How many the file holds.'],
    ['name' => 'summary[].left_out', 'type' => 'integer', 'description' => 'How many will not come over, because this product keeps nothing of that kind.'],
];

$report = ['name' => 'report', 'type' => 'array', 'description' => 'What became of the records of the file, one entry per kind, once the import has completed. Null before that, and for an import that was undone, since nothing of it stayed.'];

$reportEntries = [
    ['name' => 'report[]', 'type' => 'object', 'description' => 'One kind of record.'],
    ['name' => 'report[].kind', 'type' => 'string', 'description' => 'The kind of record: contact, file, reminder, life_event, relationship, journal_entry and so on. A kind that is only counted while the file is read, such as notes or audit_logs, keeps the name it has in the summary.'],
    ['name' => 'report[].imported', 'type' => 'integer', 'description' => 'How many came over.'],
    ['name' => 'report[].left_out', 'type' => 'integer', 'description' => 'How many did not. GET /imports/{accountImport}/report lists them with the reason.'],
];

$contactAttributes = [
    ['name' => 'name', 'type' => 'string', 'description' => 'The first and last name the file gives the contact, or null when it gives none.'],
];

$itemAttributes = [
    ['name' => 'kind', 'type' => 'string', 'description' => 'The kind of record: contact, file, reminder, life_event, relationship and so on.'],
    ['name' => 'source_uuid', 'type' => 'string', 'description' => 'The id of the record in the file, or null when the file gives it none.'],
    ['name' => 'reason', 'type' => 'string', 'description' => 'Why it did not come over, as a sentence in the language of whoever asks.'],
];

$pageParams = [
    ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Which page to answer with.', 'default' => '1', 'example' => 2],
    ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'How many records one page holds, between 1 and 100. A number outside the range is brought back into it rather than refused.', 'default' => '25', 'example' => 10],
];

return [
    'name' => 'Imports',
    'description' => 'Bring an export of Monica 4, or an export of this application, into the account. The file is read first, and nothing is written until you have seen what it holds and chosen where it goes.',
    'sections' => [
        [
            'id' => 'account-imports-show',
            'returnFields' => ApiDocsFields::one('account_import', 'import', [...$importAttributes, $summary, ...$summaryEntries, $report, ...$reportEntries]),
            'title' => 'Get an import',
            'label' => 'Get an import',
            'method' => 'GET',
            'path' => '/imports/{accountImport}',
            'examplePath' => '/imports/'.$importId,
            'description' => 'Retrieve one import: where it stands, what the file holds and, once it has completed, what became of it.',
            'body' => [
                'An import moves on its own, in the background, while its status is reading, writing or rolling_back. Ask again to follow it. An email says when it ends.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $importIdentifier,
            'returns' => 'An import object. One of another account answers 404.',
            'response' => ['data' => $completed],
        ],
        [
            'id' => 'account-imports-create',
            'returnFields' => ApiDocsFields::one('account_import', 'import', [...$importAttributes, $summary, $report]),
            'title' => 'Import a file',
            'label' => 'Import a file',
            'method' => 'POST',
            'path' => '/imports',
            'description' => 'Send an export of Monica 4, or an export of this application, to be read. Nothing is written into a vault yet.',
            'body' => [
                'Send the file as multipart form data, in a field called file. A file over 512 MB answers 422. The console of the instance imports a larger one with php artisan monica:import-account.',
                'The file is read in the background. Ask for the import until its status is read, look at what it holds, then choose where it goes with PUT /imports/{accountImport}. A file that cannot be imported ends as refused, with the reason.',
                'An account runs one import at a time. Sending a file while another import is reading, waiting for a decision, writing or being undone answers 422.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'bodyParams' => [
                ['name' => 'file', 'type' => 'file', 'required' => true, 'description' => 'The export, as the JSON file Monica 4 or this application wrote it, at most 512 MB.'],
            ],
            'returns' => 'The import object, reading the file.',
            'responseStatus' => 202,
            'response' => ['data' => $reading],
        ],
        [
            'id' => 'account-imports-update',
            'returnFields' => ApiDocsFields::one('account_import', 'import', [...$importAttributes, $summary, ...$summaryEntries, $report]),
            'title' => 'Start an import',
            'label' => 'Start an import',
            'method' => 'PUT',
            'path' => '/imports/{accountImport}',
            'examplePath' => '/imports/'.$importId,
            'description' => 'Choose where a file that has been read goes and which of its contacts is you, then start writing it.',
            'body' => [
                'Name a vault of the account with vault, or a new vault with vault_name. A file holding several vaults takes neither: each of its vaults arrives as a new vault of its own, and the contact each one marks as you is added to its Me, so me_contact is ignored too.',
                'The contact named by me_contact is added to the Me contact of the vault: its values fill only what Me leaves empty, and its relationships and life events attach to Me. Send null to import every contact as somebody else. The contacts of the file come from GET /imports/{accountImport}/contacts.',
                'The limits of the account are compared with the whole file before anything is written. A file that would take the account past its contacts, its vaults or its storage answers 422, saying which limit, and nothing is written. So does an import that is not waiting for a decision.',
                'An import only adds records. It never changes or removes one the vault already holds. It is written in the background: ask for the import to follow its progress.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $importIdentifier,
            'bodyParams' => [
                ['name' => 'vault', 'type' => 'string', 'required' => false, 'description' => 'A vault of the account to write the file into, named by its slug or its id. A vault of another account answers 404.'],
                ['name' => 'vault_name', 'type' => 'string', 'required' => false, 'description' => 'The name of a new vault to write the file into, up to 255 characters. Required when vault is left out, unless the file holds several vaults.', 'example' => 'Dunder Mifflin'],
                ['name' => 'me_contact', 'type' => 'string', 'required' => false, 'description' => 'The id, in the file, of the contact who is you, or null for none. A contact the file does not hold answers 422.', 'example' => $michaelInTheFile],
            ],
            'returns' => 'The import object, writing.',
            'responseStatus' => 202,
            'response' => ['data' => $writing],
        ],
        [
            'id' => 'account-imports-destroy',
            'returnFields' => ApiDocsFields::one('account_import', 'import', [...$importAttributes, $summary, ...$summaryEntries, $report]),
            'title' => 'Cancel an import',
            'label' => 'Cancel an import',
            'method' => 'DELETE',
            'path' => '/imports/{accountImport}',
            'examplePath' => '/imports/'.$importId,
            'description' => 'Stop an import and leave the account as it was before it started.',
            'body' => [
                'An import that is writing is undone in the background: everything it wrote is removed, a vault it created is deleted, and the answer is the import, being rolled back.',
                'An import that has not started writing is discarded with its file, and the answer is 204 with no body.',
                'An import that has ended cannot be cancelled, and answers 422.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $importIdentifier,
            'returns' => 'The import object, being rolled back.',
            'responseStatus' => 202,
            'response' => ['data' => $rollingBack],
        ],
        [
            'id' => 'account-imports-contacts-index',
            'returnFields' => ApiDocsFields::page('account_import_contact', 'contact of the file', 'contacts of the file', $contactAttributes, withLinks: false),
            'title' => 'List the contacts of a file',
            'label' => 'List the contacts of a file',
            'method' => 'GET',
            'path' => '/imports/{accountImport}/contacts',
            'examplePath' => '/imports/'.$importId.'/contacts',
            'description' => 'List the contacts a file holds, by name, to choose which of them is you.',
            'body' => [
                'The id of each is what me_contact takes when the import is started. The list is empty while the file is read.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $importIdentifier,
            'queryParams' => [
                ['name' => 'search', 'type' => 'string', 'required' => false, 'description' => 'Only the contacts whose name holds these letters, in capitals or not.', 'example' => 'Schrute'],
                ...$pageParams,
            ],
            'returns' => 'A page of the contacts of the file, sorted by name. An import of another account answers 404.',
            'response' => [
                'data' => [
                    ['type' => 'account_import_contact', 'id' => '10000000-0000-4000-8000-000000000002', 'attributes' => ['name' => 'Dwight Schrute']],
                ],
                'meta' => ['page' => 1, 'per_page' => 25, 'total' => 1],
            ],
        ],
        [
            'id' => 'account-imports-report-index',
            'returnFields' => ApiDocsFields::page('account_import_item', 'record left out', 'records left out', $itemAttributes, withLinks: false),
            'title' => 'List what an import left out',
            'label' => 'List what was left out',
            'method' => 'GET',
            'path' => '/imports/{accountImport}/report',
            'examplePath' => '/imports/'.$importId.'/report',
            'description' => 'List the records of the file that did not come over, and why.',
            'body' => [
                'Some are known as soon as the file is read, such as a reminder for a date that has passed. The rest are known once the import has ended.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $importIdentifier,
            'queryParams' => $pageParams,
            'returns' => 'A page of the records left out, in the order the file is written. An import of another account answers 404.',
            'response' => [
                'data' => [
                    ['type' => 'account_import_item', 'id' => '418', 'attributes' => ['kind' => 'reminder', 'source_uuid' => '30000000-0000-4000-8000-000000000001', 'reason' => 'A reminder for a single date that has already passed.']],
                ],
                'meta' => ['page' => 1, 'per_page' => 25, 'total' => 1],
            ],
        ],
    ],
];
