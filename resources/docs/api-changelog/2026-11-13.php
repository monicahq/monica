<?php

declare(strict_types=1);

return [
    'date' => '2026-11-13',
    'entries' => [
        [
            'id' => 'possession-field-removed-choice-values',
            'kind' => 'Changed',
            'group' => 'Possession types',
            'title' => 'Removing a choice removes the values that held it',
            'summary' => 'When a change to a select field leaves out a choice, every possession that held that choice loses its value for the field. The deletion is final.',
            'why' => 'A possession could keep a value that was no longer a choice. Writing that value back was refused, and the web form replaced it with no value without saying so.',
            'changes' => [
                ['field' => 'options', 'change' => 'Changed', 'endpoints' => ['PUT /possession-types/{possessionType}/fields/{field}']],
            ],
        ],
    ],
];
