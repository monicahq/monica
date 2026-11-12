<?php

declare(strict_types=1);

return [
    'date' => '2026-09-16',
    'entries' => [
        [
            'id' => 'vault-colour',
            'kind' => 'Added',
            'group' => 'Vaults',
            'title' => 'Adds colour to the vault object',
            'summary' => 'A vault now carries the colour it is drawn in, so Dunder Mifflin and Schrute Farms can be told apart.',
            'why' => 'Integrations drew every vault in the same grey.',
            'changes' => [
                ['field' => 'colour', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}']],
            ],
            'example' => [
                'label' => 'GET vaults/{vault}',
                'after' => '{ "name": "Dunder Mifflin", "colour": "blue" }',
            ],
        ],
    ],
];
