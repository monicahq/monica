<?php

declare(strict_types=1);

return [
    'date' => '2026-11-04',
    'note' => 'The Knowledge of a vault opens to the API.',
    'entries' => [
        [
            'id' => 'knowledge-folders',
            'kind' => 'Added',
            'group' => 'Knowledge folders',
            'title' => 'Adds Knowledge folders',
            'summary' => 'List, add, change and delete the folders of the Knowledge of a vault. Folders are flat, and a vault that holds Knowledge keeps at least one.',
            'why' => 'Folders group notes and lists by theme.',
            'changes' => [
                ['field' => 'folders', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/knowledge/folders', 'POST /vaults/{vault}/knowledge/folders', 'PUT /vaults/{vault}/knowledge/folders/{folder}', 'DELETE /vaults/{vault}/knowledge/folders/{folder}']],
            ],
        ],
        [
            'id' => 'knowledge-notes',
            'kind' => 'Added',
            'group' => 'Knowledge notes',
            'title' => 'Adds Knowledge notes and search',
            'summary' => 'List, get, write, change and delete notes written in Markdown, and search the notes and lists of a vault.',
            'why' => 'Free text has a place in a vault next to its structured records.',
            'changes' => [
                ['field' => 'notes', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/knowledge/notes', 'GET /vaults/{vault}/knowledge/notes/{note}', 'POST /vaults/{vault}/knowledge/notes', 'PUT /vaults/{vault}/knowledge/notes/{note}', 'DELETE /vaults/{vault}/knowledge/notes/{note}']],
                ['field' => 'search', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/knowledge/search']],
            ],
        ],
        [
            'id' => 'knowledge-lists',
            'kind' => 'Added',
            'group' => 'Knowledge lists',
            'title' => 'Adds Knowledge lists',
            'summary' => 'Start, get, change and delete lists, and add, change and remove their columns and items.',
            'why' => 'A list is a table the owner shapes: the owner names the columns, every column holds text, and each item holds one text per column.',
            'changes' => [
                ['field' => 'lists', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/knowledge/lists', 'GET /vaults/{vault}/knowledge/lists/{list}', 'POST /vaults/{vault}/knowledge/lists', 'PUT /vaults/{vault}/knowledge/lists/{list}', 'DELETE /vaults/{vault}/knowledge/lists/{list}']],
                ['field' => 'columns', 'change' => 'Added', 'endpoints' => ['POST /vaults/{vault}/knowledge/lists/{list}/columns', 'PUT /vaults/{vault}/knowledge/lists/{list}/columns/{column}', 'DELETE /vaults/{vault}/knowledge/lists/{list}/columns/{column}']],
                ['field' => 'items', 'change' => 'Added', 'endpoints' => ['POST /vaults/{vault}/knowledge/lists/{list}/items', 'PUT /vaults/{vault}/knowledge/lists/{list}/items/{item}', 'DELETE /vaults/{vault}/knowledge/lists/{list}/items/{item}']],
            ],
        ],
    ],
];
