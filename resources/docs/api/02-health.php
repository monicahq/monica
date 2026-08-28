<?php

declare(strict_types=1);

return [
    'name' => 'Health',
    'sections' => [
        [
            'id' => 'status-show',
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
