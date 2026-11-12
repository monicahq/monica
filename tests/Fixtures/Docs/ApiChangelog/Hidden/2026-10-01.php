<?php

declare(strict_types=1);

return [
    'date' => '2026-10-01',
    'entries' => [
        [
            'id' => 'group-budget',
            'kind' => 'Added',
            'group' => 'Groups',
            'title' => 'Adds a budget to the group object',
            'summary' => 'The Party planning committee can now record how much Angela allowed.',
            'why' => 'Oscar asked for one.',
            'changes' => [
                ['field' => 'budget', 'change' => 'Added', 'endpoints' => []],
            ],
        ],
    ],
];
