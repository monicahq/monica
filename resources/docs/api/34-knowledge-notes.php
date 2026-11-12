<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();
$application = rtrim((string) config('app.url'), '/');

$rateLimit = 'Sixty requests per minute, per user, shared by every key and device.';

$vaultSlug = 'dunder-mifflin';
$officeId = '2b7e4c91-0d5a-4f38-9c16-8e3a7f1b5d24';
$speechId = '7a2d9e4f-1c63-4b85-9f07-3e6c8a1d5b92';
$drillId = '4c8e2a61-9b37-4d05-a6f1-0e3b7c9d2a58';
$nomineesId = '6d1f8b34-2a95-4c7e-b0d3-9e5a1c7f4b26';

$webLink = 'The note in the application, which opens for somebody signed in rather than for a key. It is the link to give a person.';
$body = "## Best dressed\nPam, again.\n\n- [x] Book Chili's\n- [ ] Engrave the trophies";

$speech = [
    'type' => 'knowledge_note',
    'id' => $speechId,
    'attributes' => [
        'title' => 'Dundies speech',
        'body' => $body,
        'headings' => [['text' => 'Best dressed', 'level' => 2, 'anchor' => 'section-best-dressed']],
        'folder' => ['id' => $officeId, 'name' => 'Office'],
        'created_at' => 1773331200,
        'updated_at' => 1773417600,
    ],
    'links' => [
        'self' => "{$base}/vaults/{$vaultSlug}/knowledge/notes/{$speechId}",
        'web' => "{$application}/vaults/{$vaultSlug}/knowledge/notes/{$speechId}",
    ],
];

$nominees = [
    'type' => 'knowledge_list',
    'id' => $nomineesId,
    'attributes' => [
        'title' => 'Dundies nominees',
        'description' => 'Who might win this year.',
        'folder' => ['id' => $officeId, 'name' => 'Office'],
        'created_at' => 1773331200,
        'updated_at' => 1773417600,
    ],
    'links' => [
        'self' => "{$base}/vaults/{$vaultSlug}/knowledge/lists/{$nomineesId}",
        'web' => "{$application}/vaults/{$vaultSlug}/knowledge/lists/{$nomineesId}",
    ],
];

$listed = function (array $note): array {
    unset($note['attributes']['body'], $note['attributes']['headings']);

    return $note;
};

$drill = [
    'type' => 'knowledge_note',
    'id' => $drillId,
    'attributes' => [
        'title' => 'Fire drill',
        'folder' => ['id' => $officeId, 'name' => 'Office'],
        'created_at' => 1773504000,
        'updated_at' => 1773504000,
    ],
    'links' => [
        'self' => "{$base}/vaults/{$vaultSlug}/knowledge/notes/{$drillId}",
        'web' => "{$application}/vaults/{$vaultSlug}/knowledge/notes/{$drillId}",
    ],
];

$shared = [
    ['name' => 'title', 'type' => 'string', 'description' => 'The title of the note.'],
    ['name' => 'folder', 'type' => 'object', 'description' => 'The folder the note sits in.'],
    ['name' => 'folder.id', 'type' => 'uuid', 'description' => 'The identifier of the folder, as the knowledge folders endpoints give it.'],
    ['name' => 'folder.name', 'type' => 'string', 'description' => 'What the folder is called.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the note was written, as a count of seconds since 1970.'],
    ['name' => 'updated_at', 'type' => 'timestamp', 'description' => 'When it last changed, as a count of seconds, or null.'],
];

$attributes = [
    ...$shared,
    ['name' => 'body', 'type' => 'string', 'description' => 'The text of the note in Markdown, exactly as written, or null. It is never drawn: raw HTML in it stays text, and the application escapes it when it shows the note.'],
    ['name' => 'headings', 'type' => 'array', 'description' => 'The headings of the text, from the first level to the third, in reading order.'],
    ['name' => 'headings[]', 'type' => 'object', 'description' => 'One heading.'],
    ['name' => 'headings[].text', 'type' => 'string', 'description' => 'What the heading says.'],
    ['name' => 'headings[].level', 'type' => 'integer', 'description' => 'Its level, 1 to 3.'],
    ['name' => 'headings[].anchor', 'type' => 'string', 'description' => 'The anchor the application gives it, to open the note at that heading with links.web followed by # and the anchor.'],
];

$vaultParam = ['name' => 'vault', 'type' => 'string', 'required' => true, 'description' => 'The vault, named either by the readable name it reports as slug or by the identifier it reports as id. A vault of another account answers 404.'];
$noteParam = ['name' => 'note', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of the note. A note of another vault or of another account answers 404.'];
$folderParam = 'The identifier of a folder of the vault, as the knowledge folders endpoints give it. A folder of another vault answers 404.';
$titleParam = 'The title of the note, up to 255 characters. Markup is stripped.';
$bodyParam = 'The text of the note in Markdown, up to 200,000 characters: # for a heading, - for a bullet, - [ ] for a task, > for a quote, **bold**. Web addresses become links on their own.';

return [
    'name' => 'Knowledge notes',
    'description' => 'Free text kept in a folder of a vault\'s Knowledge, written in Markdown. A note links to no contact, possession or other note.',
    'sections' => [
        [
            'id' => 'knowledge-notes-index',
            'returnFields' => [
                ['name' => 'data', 'type' => 'array', 'description' => 'Every note, whole rather than a page at a time.'],
                ['name' => 'data[]', 'type' => 'object', 'description' => 'One knowledge note, without its text.'],
                ['name' => 'data[].type', 'type' => 'string', 'description' => 'Always knowledge_note.'],
                ['name' => 'data[].id', 'type' => 'string', 'description' => 'The identifier of the note, which every address naming it takes.'],
                ['name' => 'data[].attributes', 'type' => 'object', 'description' => 'What the note is, without its text.'],
                ...array_map(fn (array $attribute): array => [...$attribute, 'name' => "data[].attributes.{$attribute['name']}"], $shared),
                ['name' => 'data[].links', 'type' => 'object', 'description' => 'Where to find this note.'],
                ['name' => 'data[].links.self', 'type' => 'string', 'description' => 'The absolute address the whole note is read from.'],
                ['name' => 'data[].links.web', 'type' => 'string', 'description' => $webLink],
            ],
            'title' => 'List the notes of a vault',
            'label' => 'List notes',
            'method' => 'GET',
            'path' => '/vaults/{vault}/knowledge/notes',
            'examplePath' => "/vaults/{$vaultSlug}/knowledge/notes",
            'description' => 'List the notes of a vault\'s Knowledge by title, without their text.',
            'body' => ['A note can be long, so the list leaves its text out. Read one note to get it.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam],
            'queryParams' => [
                ['name' => 'folder', 'type' => 'uuid', 'required' => false, 'description' => "Only the notes of this folder. {$folderParam}"],
            ],
            'returns' => 'Every knowledge note object of the vault, without its text, or an empty list. A vault of another account answers 404.',
            'response' => ['data' => [$listed($speech), $drill]],
        ],
        [
            'id' => 'knowledge-notes-show',
            'returnFields' => ApiDocsFields::one('knowledge_note', 'knowledge note', $attributes, webLink: $webLink),
            'title' => 'Get a note',
            'method' => 'GET',
            'path' => '/vaults/{vault}/knowledge/notes/{note}',
            'examplePath' => "/vaults/{$vaultSlug}/knowledge/notes/{$speechId}",
            'description' => 'Retrieve one note with its text and its headings.',
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $noteParam],
            'returns' => 'A knowledge note object. A note of another vault or of another account answers 404.',
            'response' => ['data' => $speech],
        ],
        [
            'id' => 'knowledge-notes-create',
            'returnFields' => ApiDocsFields::one('knowledge_note', 'knowledge note', $attributes, webLink: $webLink),
            'title' => 'Write a note',
            'method' => 'POST',
            'path' => '/vaults/{vault}/knowledge/notes',
            'examplePath' => "/vaults/{$vaultSlug}/knowledge/notes",
            'description' => 'Write a note in a vault\'s Knowledge.',
            'body' => [
                'Every field is optional. A note without a title is called “Untitled note”. Without a folder it goes to the first folder of the vault, and a vault without any folder gets one called “General” to hold it.',
                'The change is written to the account log.',
            ],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam],
            'bodyParams' => [
                ['name' => 'title', 'type' => 'string', 'required' => false, 'description' => $titleParam, 'example' => 'Dundies speech'],
                ['name' => 'body', 'type' => 'string', 'required' => false, 'description' => $bodyParam, 'example' => $body],
                ['name' => 'folder_id', 'type' => 'uuid', 'required' => false, 'description' => $folderParam, 'example' => $officeId],
            ],
            'returns' => 'The knowledge note object that was written.',
            'responseStatus' => 201,
            'response' => ['data' => $speech],
        ],
        [
            'id' => 'knowledge-notes-update',
            'returnFields' => ApiDocsFields::one('knowledge_note', 'knowledge note', $attributes, webLink: $webLink),
            'title' => 'Change a note',
            'method' => 'PUT',
            'path' => '/vaults/{vault}/knowledge/notes/{note}',
            'examplePath' => "/vaults/{$vaultSlug}/knowledge/notes/{$speechId}",
            'description' => 'Change the title, the text or the folder of a note.',
            'body' => [
                'A field left out keeps its value. A title left empty keeps the one the note had, and a text sent as null or empty clears it. The text replaces the whole text, so a task is ticked by sending the text back with [x] in place of [ ].',
                'The change is written to the account log.',
            ],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $noteParam],
            'bodyParams' => [
                ['name' => 'title', 'type' => 'string', 'required' => false, 'description' => $titleParam],
                ['name' => 'body', 'type' => 'string', 'required' => false, 'description' => $bodyParam, 'example' => $body],
                ['name' => 'folder_id', 'type' => 'uuid', 'required' => false, 'description' => "The folder to move the note to. {$folderParam}"],
            ],
            'returns' => 'The knowledge note object as it now reads. A note of another vault or of another account answers 404.',
            'response' => ['data' => $speech],
        ],
        [
            'id' => 'knowledge-notes-destroy',
            'title' => 'Delete a note',
            'method' => 'DELETE',
            'path' => '/vaults/{vault}/knowledge/notes/{note}',
            'examplePath' => "/vaults/{$vaultSlug}/knowledge/notes/{$drillId}",
            'description' => 'Delete a note of a vault\'s Knowledge.',
            'body' => ['The deletion is final. The account log keeps the title of the note.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $noteParam],
            'returns' => 'Nothing. A note of another vault or of another account answers 404.',
            'responseStatus' => 204,
            'response' => [],
        ],
        [
            'id' => 'knowledge-search',
            'returnFields' => [
                ['name' => 'data', 'type' => 'array', 'description' => 'Every note found, then every list found, whole rather than a page at a time.'],
                ['name' => 'data[]', 'type' => 'object', 'description' => 'One knowledge note, or one knowledge list as the knowledge lists endpoints describe it, told apart by type.'],
                ['name' => 'data[].type', 'type' => 'string', 'description' => 'knowledge_note or knowledge_list.'],
                ['name' => 'data[].id', 'type' => 'string', 'description' => 'The identifier of the note or the list.'],
                ['name' => 'data[].attributes', 'type' => 'object', 'description' => 'Everything the note holds, or what the list is without its columns and items.'],
                ...array_map(fn (array $attribute): array => [...$attribute, 'name' => "data[].attributes.{$attribute['name']}"], $attributes),
                ['name' => 'data[].attributes.description', 'type' => 'string', 'description' => 'For a list, what it is for, in Markdown as written, or null. A note carries none.'],
                ['name' => 'data[].links', 'type' => 'object', 'description' => 'Where to find this note or list.'],
                ['name' => 'data[].links.self', 'type' => 'string', 'description' => 'The absolute address it is read from.'],
                ['name' => 'data[].links.web', 'type' => 'string', 'description' => 'The note or the list in the application.'],
            ],
            'title' => 'Search notes and lists',
            'label' => 'Search Knowledge',
            'method' => 'GET',
            'path' => '/vaults/{vault}/knowledge/search',
            'examplePath' => "/vaults/{$vaultSlug}/knowledge/search?query=dundies",
            'description' => 'Find the notes and lists of a vault\'s Knowledge holding every word given.',
            'body' => ['A note or a list is found when each word is in its title, its text, its description or any of its cells. Case and accents are ignored as the language of the account ignores them, a word is found at the start of a longer one, and one typing mistake is tolerated from five letters, two from nine.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam],
            'queryParams' => [
                ['name' => 'query', 'type' => 'string', 'required' => true, 'description' => 'The words to find, at least two characters and up to 255.'],
            ],
            'returns' => 'The notes and the lists found, or an empty list. A vault of another account answers 404.',
            'response' => ['data' => [$speech, $nominees]],
        ],
    ],
];
