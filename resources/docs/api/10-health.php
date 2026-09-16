<?php

declare(strict_types=1);

return [
    'name' => 'Health',
    'description' => 'Whether the instance is answering, and whether what it depends on is answering it.',
    'sections' => [
        [
            'id' => 'status-show',
            'returnFields' => [
                ['name' => 'status', 'type' => 'string', 'description' => 'Always ok in this answer. An instance that cannot reach its database answers 503 instead, carrying status of error and nothing else, and one that is not running answers nothing at all. Those three are what a monitor watches for.'],
                ['name' => 'version', 'type' => 'string', 'description' => 'The release the instance runs, as a semantic version such as 3.0.0. It is how a caller can tell whether a capability it needs has landed here yet.'],
                ['name' => 'instance', 'type' => 'string', 'description' => 'The host answering, so a monitor watching several self hosted instances can tell them apart.'],
            ],
            'title' => 'Check status',
            'label' => 'Check status',
            'method' => 'GET',
            'path' => '/status',
            'auth' => false,
            'description' => 'Tells you whether the instance is up and which release it runs. No token needed, so you can point a monitor at it.',
            'rateLimit' => 'Sixty requests per minute, per address.',
            'returns' => 'The state of the instance, or a 503 status when something it depends on is unreachable.',
            'response' => [
                'status' => 'ok',
                'version' => '3.0.0',
                'instance' => 'app.monicahq.com',
            ],
        ],
    ],
];
