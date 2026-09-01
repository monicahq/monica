<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();

$log = [
    'type' => 'log',
    'id' => '12',
    'attributes' => [
        'author' => 'Michael Scott',
        'action' => 'api_key_created',
        'parameters' => ['name' => 'Dundie awards bot'],
        'description' => 'Created the API key called Dundie awards bot',
        'source' => 'web',
        'ip_address' => '192.168.1.24',
        'created_at' => 1756339200,
    ],
    'links' => [
        'self' => $base.'/logs/12',
    ],
];

$systemLog = [
    'type' => 'log',
    'id' => '11',
    'attributes' => [
        'author' => 'The system',
        'action' => 'account_created',
        'parameters' => null,
        'description' => 'Created the account',
        'source' => 'system',
        'ip_address' => null,
        'created_at' => 1756252800,
    ],
    'links' => [
        'self' => $base.'/logs/11',
    ],
];

$logAttributes = [
    ['name' => 'author', 'type' => 'string', 'description' => 'Who did it: their name while they are still on the account, the address they had once they are not, and a translated phrase reading The system for anything the product did on its own. Worked out when you ask rather than stored, so a deleted person still reads as somebody.'],
    ['name' => 'action', 'type' => 'string', 'description' => 'What was done, as a key such as vault_created or contact_deleted, up to 255 characters. The keys come from a set the product keeps and adds to as it gains capabilities, and the column is a plain string rather than a fixed set, so match the ones you care about and let an unfamiliar one through rather than treating it as an error.'],
    ['name' => 'parameters', 'type' => 'object', 'description' => 'What the action was about, as whatever that kind of action recorded. The keys depend entirely on the action, so nothing here promises a shape: a vault creation carries the name of the vault, another action carries something else.', 'opaque' => true],
    ['name' => 'description', 'type' => 'string', 'description' => 'The entry as a sentence a person can read, with the parameters already put into it. Worked out when you ask, and in the language of whoever is asking.'],
    ['name' => 'source', 'type' => 'string', 'description' => 'Where it came from: one of web, api, mcp, import, automation, module or system. It is how a change made by a script is told from one made by hand.'],
    ['name' => 'ip_address', 'type' => 'string', 'description' => 'The address the request came from, up to 45 characters so that a version six address fits, or null when nothing was recorded, which is the case for anything the system did on its own.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When it happened, as a count of seconds since 1970. The journal is append only, so this never moves.'],
];

return [
    'name' => 'Logs',
    'sections' => [
        [
            'id' => 'logs-index',
            'returnFields' => ApiDocsFields::page('log', 'entry', 'entries', $logAttributes),
            'title' => 'List logs',
            'label' => 'List logs',
            'method' => 'GET',
            'path' => '/logs',
            'description' => 'List what has been done in your account, newest first.',
            'body' => [
                'The journal belongs to the account rather than to you, which matters in one case: an entry the application wrote on its own has nobody behind it, and those are exactly the entries nobody watched happen. Its author reads as the system and it carries no address.',
                'Each entry carries both the raw name of the action, which is what a program matches on, and a sentence in the language on your account, which is what a person reads.',
                'Nothing here can be written, changed or removed. A journal that can be rewritten is worth nothing, so there is no endpoint for it.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'queryParams' => [
                [
                    'name' => 'page',
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'Which page to answer with.',
                    'default' => '1',
                ],
                [
                    'name' => 'per_page',
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'How many entries one page holds, between 1 and 100. A number outside the range is brought back into it rather than refused.',
                    'default' => '25',
                ],
            ],
            'returns' => 'A page of journal entries, and how many there are in all.',
            'response' => [
                'data' => [$log, $systemLog],
                'meta' => [
                    'page' => 1,
                    'per_page' => 25,
                    'total' => 2,
                ],
            ],
        ],
        [
            'id' => 'logs-show',
            'returnFields' => ApiDocsFields::one('log', 'entry', $logAttributes),
            'title' => 'Get a log',
            'label' => 'Get a log',
            'method' => 'GET',
            'path' => '/logs/{log}',
            'examplePath' => '/logs/12',
            'description' => 'Retrieve one entry of your account journal.',
            'body' => [
                'What changed is not in here. A field somebody marked sensitive records that it changed and not what to, and until the product settles how those values may be kept, the whole column stays out of the API rather than half of it coming through.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => [
                [
                    'name' => 'log',
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'The identifier of the entry.',
                ],
            ],
            'returns' => 'A journal entry. An entry from another account answers 404.',
            'response' => ['data' => $log],
        ],
    ],
];
