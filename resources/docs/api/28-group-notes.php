<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();

$vaultSlug = 'dunder-mifflin';
$groupId = '5d2a7c91-3e48-4b06-9f1d-a83c60e2b4f7';
$noteId = '9e3c51b7-2d84-4f60-a1c9-6b07d4e8f235';

$note = [
    'type' => 'group_note',
    'id' => $noteId,
    'attributes' => [
        'group' => [
            'id' => $groupId,
            'name' => 'Party planning committee',
        ],
        'body' => 'Angela keeps the budget in the blue binder. Ask her before buying anything.',
        'created_at' => 1791547200,
        'updated_at' => 1791547200,
    ],
    'links' => [
        'self' => $base.'/vaults/'.$vaultSlug.'/groups/'.$groupId.'/notes/'.$noteId,
    ],
];

$flagNote = 'Groups are not yet available to every account. Until they are available to yours, every endpoint here answers 404.';

$groupIdentifier = [
    ['name' => 'vault', 'type' => 'string', 'required' => true, 'description' => 'The vault the group lives in, named either by the readable name it reports as slug or by its id.'],
    ['name' => 'group', 'type' => 'uuid', 'required' => true, 'description' => 'The id of the group. One addressed through the wrong vault, deleted, or belonging to another account, answers 404.'],
];

$noteIdentifier = [
    ...$groupIdentifier,
    ['name' => 'note', 'type' => 'uuid', 'required' => true, 'description' => 'The id of the note. One addressed through another group answers 404.'],
];

$noteAttributes = [
    ['name' => 'group', 'type' => 'object', 'description' => 'The group the note is about.'],
    ['name' => 'group.id', 'type' => 'uuid', 'description' => 'The id of the group.'],
    ['name' => 'group.name', 'type' => 'string', 'description' => 'What the group is called.'],
    ['name' => 'body', 'type' => 'string', 'description' => 'What was written, as plain text.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the note was written, as a count of seconds since 1970. This is the date the group page shows.'],
    ['name' => 'updated_at', 'type' => 'timestamp', 'description' => 'When it last changed, as a count of seconds since 1970, or null.'],
];

$bodyParam = ['name' => 'body', 'type' => 'string', 'required' => true, 'description' => 'What to write, as plain text, up to 65,535 characters. Any markup is taken out. A body left empty answers 422.', 'example' => 'Angela keeps the budget in the blue binder. Ask her before buying anything.'];

return [
    'name' => 'Group notes',
    'description' => 'What is written down about a group as a whole: where they usually meet, who has the spare key, what to bring. A note belongs to the group alone and never appears on the page of anybody in it.',
    'sections' => [
        [
            'id' => 'group-notes-index',
            'flag' => 'groups',
            'returnFields' => ApiDocsFields::page('group_note', 'note', 'notes', $noteAttributes),
            'title' => 'List the notes of a group',
            'label' => 'List notes',
            'method' => 'GET',
            'path' => '/vaults/{vault}/groups/{group}/notes',
            'examplePath' => '/vaults/'.$vaultSlug.'/groups/'.$groupId.'/notes',
            'description' => 'List the notes written about a group, the newest first.',
            'body' => [$flagNote],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $groupIdentifier,
            'queryParams' => [
                ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Which page to answer with.', 'default' => '1'],
                ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'How many records one page holds, between 1 and 100. A number outside the range is brought back into it rather than refused.', 'default' => '25'],
            ],
            'returns' => 'A page of note objects, and how many there are in all.',
            'response' => [
                'data' => [$note],
                'meta' => ['page' => 1, 'per_page' => 25, 'total' => 1],
            ],
        ],
        [
            'id' => 'group-notes-show',
            'flag' => 'groups',
            'returnFields' => ApiDocsFields::one('group_note', 'note', $noteAttributes),
            'title' => 'Get a note',
            'label' => 'Get a note',
            'method' => 'GET',
            'path' => '/vaults/{vault}/groups/{group}/notes/{note}',
            'examplePath' => '/vaults/'.$vaultSlug.'/groups/'.$groupId.'/notes/'.$noteId,
            'description' => 'Return one note written about a group.',
            'body' => [$flagNote],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $noteIdentifier,
            'returns' => 'The note object. A note of another account, or one already deleted, answers 404.',
            'response' => ['data' => $note],
        ],
        [
            'id' => 'group-notes-create',
            'flag' => 'groups',
            'returnFields' => ApiDocsFields::one('group_note', 'note', $noteAttributes),
            'title' => 'Write a note on a group',
            'label' => 'Write a note',
            'method' => 'POST',
            'path' => '/vaults/{vault}/groups/{group}/notes',
            'examplePath' => '/vaults/'.$vaultSlug.'/groups/'.$groupId.'/notes',
            'description' => 'Write a note about a group as a whole. It is dated the day it is written, and the change is written to the account log.',
            'body' => [$flagNote],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $groupIdentifier,
            'bodyParams' => [$bodyParam],
            'returns' => 'The note object that was written.',
            'responseStatus' => 201,
            'response' => ['data' => $note],
        ],
        [
            'id' => 'group-notes-update',
            'flag' => 'groups',
            'returnFields' => ApiDocsFields::one('group_note', 'note', $noteAttributes),
            'title' => 'Change a note',
            'label' => 'Change a note',
            'method' => 'PUT',
            'path' => '/vaults/{vault}/groups/{group}/notes/{note}',
            'examplePath' => '/vaults/'.$vaultSlug.'/groups/'.$groupId.'/notes/'.$noteId,
            'description' => 'Change what a note says. It keeps the day it was written.',
            'body' => [$flagNote],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $noteIdentifier,
            'bodyParams' => [[...$bodyParam, 'required' => false, 'description' => 'What to write, as plain text, up to 65,535 characters. Any markup is taken out. Left out, the note keeps what it says. A body sent as null or left empty answers 422.']],
            'returns' => 'The note object as it stands now.',
            'response' => ['data' => $note],
        ],
        [
            'id' => 'group-notes-destroy',
            'flag' => 'groups',
            'title' => 'Delete a note',
            'label' => 'Delete a note',
            'method' => 'DELETE',
            'path' => '/vaults/{vault}/groups/{group}/notes/{note}',
            'examplePath' => '/vaults/'.$vaultSlug.'/groups/'.$groupId.'/notes/'.$noteId,
            'description' => 'Delete a note about a group. Nothing else changes.',
            'body' => [$flagNote],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $noteIdentifier,
            'returns' => 'Nothing at all.',
            'responseStatus' => 204,
        ],
    ],
];
