<?php

declare(strict_types=1);

return [
    'date' => '2026-11-26',
    'entries' => [
        [
            'id' => 'support-messages-markdown',
            'kind' => 'Changed',
            'group' => 'Support requests',
            'title' => 'Support messages are written in Markdown',
            'summary' => 'The body of a support request, a message, a reply and a note is now Markdown, kept exactly as written. It is no longer stripped of anything that looks like an HTML tag, so it can contain < and >. Format it safely before displaying it, escaping raw HTML and refusing links that are not web or email links, and never insert it into a page as HTML.',
            'why' => 'The support screens now show messages formatted, and a request often holds steps and pasted errors that read better as lists and code.',
            'breaking' => false,
            'changes' => [
                ['field' => 'body', 'change' => 'Changed', 'endpoints' => [
                    'GET /support-requests/{supportRequest}',
                    'POST /support-requests',
                    'POST /support-requests/{supportRequest}/messages',
                    'GET /admin/support-requests/{supportRequest}',
                    'POST /admin/support-requests/{supportRequest}/replies',
                    'POST /admin/support-requests/{supportRequest}/notes',
                ]],
            ],
        ],
    ],
];
