<?php

declare(strict_types=1);

return [
    'date' => '2026-01-01',
    'entries' => [
        ['id' => 'no-summary', 'kind' => 'Added', 'group' => 'Vaults', 'title' => 'Adds a summary nobody wrote', 'why' => 'Kevin forgot.', 'changes' => [['field' => 'x', 'change' => 'Added', 'endpoints' => []]]],
        ['id' => 'unknown-kind', 'kind' => 'Improved', 'group' => 'Vaults', 'title' => 'Improves the chili', 'summary' => 'Better.', 'why' => 'Kevin.', 'changes' => [['field' => 'x', 'change' => 'Added', 'endpoints' => []]]],
        ['id' => 'unknown-group', 'kind' => 'Added', 'group' => 'Paper sales', 'title' => 'Adds paper', 'summary' => 'Paper.', 'why' => 'Paper.', 'changes' => [['field' => 'x', 'change' => 'Added', 'endpoints' => []]]],
        ['id' => 'no-impact', 'kind' => 'Removed', 'group' => 'Vaults', 'title' => 'Removes the annex', 'summary' => 'Gone.', 'why' => 'Gone.', 'breaking' => true, 'changes' => [['field' => 'x', 'change' => 'Removed', 'endpoints' => []]]],
        ['id' => 'no-summary', 'kind' => 'Added', 'group' => 'Vaults', 'title' => 'Shares an id', 'summary' => 'Twice.', 'why' => 'Twice.', 'changes' => [['field' => 'x', 'change' => 'Added', 'endpoints' => []]]],
    ],
];
