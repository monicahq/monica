<?php

declare(strict_types=1);

return [
    'date' => '2026-11-25',
    'entries' => [
        [
            'id' => 'changelog-entry-screenshots',
            'kind' => 'Added',
            'group' => 'What’s new',
            'title' => 'Read the screenshots of a change',
            'summary' => 'A changelog entry now carries screenshots, a list of the screenshots that show the change, in order. Each one gives its position, the absolute url of a WebP image, a description in your language, and its width and height in pixels. The list is empty when the change has none.',
            'why' => 'What’s new now shows screenshots under a change, and everything the application shows can be read through the API.',
            'breaking' => false,
            'changes' => [
                ['field' => 'screenshots', 'change' => 'Added', 'endpoints' => ['GET /changelog-entries']],
            ],
        ],
    ],
];
