<?php

declare(strict_types=1);

return [
    'date' => '2026-10-10',
    'note' => 'The first endpoints of the inventory: locations and possession types.',
    'entries' => [
        [
            'id' => 'locations',
            'kind' => 'Added',
            'group' => 'Locations',
            'title' => 'Adds locations',
            'summary' => 'List, get, add, change and delete the places the things of your account are kept in. A location can sit inside another as deep as you like.',
            'why' => 'Possessions point at a location, so the places are managed in one set of endpoints. A location that holds others cannot be deleted.',
            'changes' => [
                ['field' => 'locations', 'change' => 'Added', 'endpoints' => ['GET /locations', 'GET /locations/{location}', 'POST /locations', 'PUT /locations/{location}', 'DELETE /locations/{location}']],
            ],
        ],
        [
            'id' => 'possession-types',
            'kind' => 'Added',
            'group' => 'Possession types',
            'title' => 'Adds possession types',
            'summary' => 'List, get, add, change and delete the kinds of things your account owns.',
            'why' => 'A possession type decides which fields a possession of that kind carries.',
            'changes' => [
                ['field' => 'possession types', 'change' => 'Added', 'endpoints' => ['GET /possession-types', 'GET /possession-types/{possessionType}', 'POST /possession-types', 'PUT /possession-types/{possessionType}', 'DELETE /possession-types/{possessionType}']],
            ],
        ],
        [
            'id' => 'possession-type-fields',
            'kind' => 'Added',
            'group' => 'Possession types',
            'title' => 'Adds groups and fields of a possession type',
            'summary' => 'Add, rename, remove and move the groups of fields of a type, and add, change, remove and move its fields.',
            'why' => 'The fields of each kind of thing are written by the account, not fixed by the product.',
            'changes' => [
                ['field' => 'groups', 'change' => 'Added', 'endpoints' => ['POST /possession-types/{possessionType}/groups', 'PUT /possession-types/{possessionType}/groups/{group}', 'DELETE /possession-types/{possessionType}/groups/{group}', 'PUT /possession-types/{possessionType}/groups/{group}/position']],
                ['field' => 'fields', 'change' => 'Added', 'endpoints' => ['POST /possession-types/{possessionType}/fields', 'PUT /possession-types/{possessionType}/fields/{field}', 'DELETE /possession-types/{possessionType}/fields/{field}', 'PUT /possession-types/{possessionType}/fields/{field}/position']],
            ],
        ],
    ],
];
