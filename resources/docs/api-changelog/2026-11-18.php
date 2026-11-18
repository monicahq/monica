<?php

declare(strict_types=1);

return [
    'date' => '2026-11-18',
    'entries' => [
        [
            'id' => 'agent-feedback',
            'kind' => 'Added',
            'group' => 'Agent feedback',
            'title' => 'Adds agent feedback',
            'summary' => 'An AI agent sends the administrators of the instance a report about a tool that failed, was missing or misled it, with what it tried and the fix it suggests. Administrators list the reports, read one, leave notes and move it between new, accepted, fixed and dismissed.',
            'why' => 'An agent that met a broken or missing tool had no way to say so, and the people who run the instance never heard about it.',
            'changes' => [
                ['field' => 'agent_feedback', 'change' => 'Added', 'endpoints' => ['POST /agent-feedback']],
                ['field' => 'agent_feedback_report', 'change' => 'Added', 'endpoints' => ['GET /admin/agent-feedback', 'GET /admin/agent-feedback/{agentFeedbackReport}', 'POST /admin/agent-feedback/{agentFeedbackReport}/notes', 'PUT /admin/agent-feedback/{agentFeedbackReport}/status']],
            ],
            'example' => [
                'label' => 'POST agent-feedback',
                'after' => '{ "kind": "bug", "severity": "blocks_task", "title": "Yearly reminders on 29 February are refused", "observed": "...", "expected": "...", "proposed_fix": "..." }',
            ],
        ],
    ],
];
