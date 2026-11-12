<?php

declare(strict_types=1);

return [
    'date' => '2026-10-17',
    'entries' => [
        [
            'id' => 'possessions',
            'kind' => 'Added',
            'group' => 'Possessions',
            'title' => 'Adds possessions',
            'summary' => 'List, get, add, change and delete the things a vault holds, move one in the list, and write its values.',
            'why' => 'Possessions are the center of the inventory. The other possession endpoints hang from them.',
            'changes' => [
                ['field' => 'possessions', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/possessions', 'GET /vaults/{vault}/possessions/{possession}', 'POST /vaults/{vault}/possessions', 'PUT /vaults/{vault}/possessions/{possession}', 'DELETE /vaults/{vault}/possessions/{possession}']],
                ['field' => 'position', 'change' => 'Added', 'endpoints' => ['PUT /vaults/{vault}/possessions/{possession}/position']],
                ['field' => 'values', 'change' => 'Added', 'endpoints' => ['PUT /vaults/{vault}/possessions/{possession}/values']],
            ],
        ],
        [
            'id' => 'possession-tags',
            'kind' => 'Added',
            'group' => 'Possession tags',
            'title' => 'Adds possession tags',
            'summary' => 'List, get, add, change and delete the words your account puts on its possessions.',
            'why' => 'Tags sort possessions across types and locations.',
            'changes' => [
                ['field' => 'possession tags', 'change' => 'Added', 'endpoints' => ['GET /possession-tags', 'GET /possession-tags/{possessionTag}', 'POST /possession-tags', 'PUT /possession-tags/{possessionTag}', 'DELETE /possession-tags/{possessionTag}']],
            ],
        ],
        [
            'id' => 'possession-conditions',
            'kind' => 'Added',
            'group' => 'Possession conditions',
            'title' => 'Adds possession conditions',
            'summary' => 'List, get, add, change, move and delete the states the possessions of your account can be in, from best to worst.',
            'why' => 'A condition is named by the account, and its position says how good it is.',
            'changes' => [
                ['field' => 'possession conditions', 'change' => 'Added', 'endpoints' => ['GET /possession-conditions', 'GET /possession-conditions/{possessionCondition}', 'POST /possession-conditions', 'PUT /possession-conditions/{possessionCondition}', 'PUT /possession-conditions/{possessionCondition}/position', 'DELETE /possession-conditions/{possessionCondition}']],
            ],
        ],
        [
            'id' => 'possession-type-exchange',
            'kind' => 'Added',
            'group' => 'Possession types',
            'title' => 'Adds export and import of a possession type',
            'summary' => 'Export a possession type with its groups and fields, and import one to create the same type in another account.',
            'why' => 'A type written once can be reused without writing its fields again.',
            'changes' => [
                ['field' => 'export', 'change' => 'Added', 'endpoints' => ['GET /possession-types/{possessionType}/export']],
                ['field' => 'import', 'change' => 'Added', 'endpoints' => ['POST /possession-types/import']],
            ],
        ],
    ],
];
