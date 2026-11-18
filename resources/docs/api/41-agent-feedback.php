<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();

$reportId = '3d9b7e21-6f4a-4c85-9a02-b81e5c47d613';

$steps = [
    ['tool' => 'create-contact-reminder', 'arguments' => '{"contact": "<contact>", "day": 29, "month": 2}', 'outcome' => 'error', 'result' => 'The date field must be a valid date.'],
];

$report = [
    'type' => 'agent_feedback_report',
    'id' => $reportId,
    'attributes' => [
        'number' => 48,
        'status' => 'new',
        'kind' => 'bug',
        'severity' => 'blocks_task',
        'title' => 'Yearly reminders on 29 February are refused',
        'tool' => 'create-contact-reminder',
        'request' => 'Add a yearly reminder for the birthday of a cousin born on 29 February.',
        'steps' => $steps,
        'observed' => 'Any reminder on 29 February fails validation, with or without a year.',
        'expected' => 'The reminder is created and falls on 28 February in other years.',
        'proposed_fix' => 'Validate the day against a leap year when no year is given.',
        'proposed_code' => null,
        'model' => 'Claude Opus',
        'connection_name' => 'Claude Desktop',
        'account_id' => 12,
        'user' => ['name' => 'Michael Scott', 'email' => 'michael.scott@dundermifflin.test'],
        'submitted_at' => 1791547200,
    ],
    'links' => [
        'self' => $base.'/admin/agent-feedback/'.$reportId,
    ],
];

$note = [
    'id' => '9f2c4a71-0d8e-4b36-a5c1-7e3b90d26f48',
    'author_name' => 'Jim Halpert',
    'body' => 'Confirmed on a test account. Planned for the next release.',
    'created_at' => 1791550800,
];

$reportIdentifier = [
    ['name' => 'agentFeedbackReport', 'type' => 'uuid', 'required' => true, 'description' => 'The id of the report, of any account on the instance.'],
];

$administrationNote = 'Only an administrator of this instance can use this endpoint, and only with an API key that carries the administration scope by name. Anybody else, including an administrator whose key does not carry it, gets 404 as though the endpoint did not exist.';

$rateLimit = 'Sixty requests per minute, per user, shared by every key and device.';

$reportAttributes = [
    ['name' => 'number', 'type' => 'integer', 'description' => 'The number administrators read the report by, such as 48 for #48.'],
    ['name' => 'status', 'type' => 'string', 'description' => 'new, accepted, fixed or dismissed.'],
    ['name' => 'kind', 'type' => 'string', 'description' => 'bug, missing_tool, unclear_description or improvement.'],
    ['name' => 'severity', 'type' => 'string', 'description' => 'blocks_task, needs_workaround or minor.'],
    ['name' => 'title', 'type' => 'string', 'description' => 'One line naming the problem.'],
    ['name' => 'tool', 'type' => 'string', 'description' => 'The MCP tool the report is about, as the agent wrote it, or null.'],
    ['name' => 'request', 'type' => 'string', 'description' => 'What the person asked, paraphrased by the agent, or null.'],
    ['name' => 'steps', 'type' => 'array', 'description' => 'The tool calls the agent tried, in order. An empty list when it sent none.'],
    ['name' => 'steps[]', 'type' => 'object', 'description' => 'One tool call.'],
    ['name' => 'steps[].tool', 'type' => 'string', 'description' => 'The tool it called.'],
    ['name' => 'steps[].arguments', 'type' => 'string', 'description' => 'The arguments it sent, as the agent wrote them, or null.'],
    ['name' => 'steps[].outcome', 'type' => 'string', 'description' => 'success or error.'],
    ['name' => 'steps[].result', 'type' => 'string', 'description' => 'What came back, or null.'],
    ['name' => 'observed', 'type' => 'string', 'description' => 'What happened.'],
    ['name' => 'expected', 'type' => 'string', 'description' => 'What should have happened.'],
    ['name' => 'proposed_fix', 'type' => 'string', 'description' => 'The fix the agent suggests.'],
    ['name' => 'proposed_code', 'type' => 'string', 'description' => 'Code the agent suggests for the fix, or null.'],
    ['name' => 'model', 'type' => 'string', 'description' => 'The model the agent says it runs on, or null.'],
    ['name' => 'connection_name', 'type' => 'string', 'description' => 'The connected assistant or the API key the report came through.'],
    ['name' => 'account_id', 'type' => 'integer', 'description' => 'The account the agent was working in.'],
    ['name' => 'user', 'type' => 'object', 'description' => 'Who the agent was working for, or null once their user is deleted.'],
    ['name' => 'user.name', 'type' => 'string', 'description' => 'Their name.'],
    ['name' => 'user.email', 'type' => 'string', 'description' => 'Their email address.'],
    ['name' => 'submitted_at', 'type' => 'timestamp', 'description' => 'When the report was sent, as a count of seconds since 1970.'],
];

$noteAttributes = [
    ['name' => 'notes', 'type' => 'array', 'description' => 'The notes administrators left on it, the oldest first.'],
    ['name' => 'notes[]', 'type' => 'object', 'description' => 'One note.'],
    ['name' => 'notes[].id', 'type' => 'uuid', 'description' => 'The id of the note.'],
    ['name' => 'notes[].author_name', 'type' => 'string', 'description' => 'The name of the administrator who wrote it.'],
    ['name' => 'notes[].body', 'type' => 'string', 'description' => 'What was written, as plain text.'],
    ['name' => 'notes[].created_at', 'type' => 'timestamp', 'description' => 'When it was written, as a count of seconds since 1970.'],
];

return [
    'name' => 'Agent feedback',
    'description' => 'An AI agent connected through MCP tells the administrators of this instance that a tool failed, was missing or misled it, with what it tried and the fix it suggests. Administrators read the reports, leave notes and move them between statuses. The agent is asked to paraphrase the request and to leave names, dates and other details from the vault out.',
    'sections' => [
        [
            'id' => 'admin-agent-feedback-index',
            'returnFields' => [
                ...ApiDocsFields::page('agent_feedback_report', 'report', 'reports', $reportAttributes),
                ['name' => 'meta.counts', 'type' => 'object', 'description' => 'How many reports each status holds under the filters you sent, apart from the status itself.'],
                ['name' => 'meta.counts.new', 'type' => 'integer', 'description' => 'Reports nobody has looked at.'],
                ['name' => 'meta.counts.accepted', 'type' => 'integer', 'description' => 'Reports accepted and waiting for a fix.'],
                ['name' => 'meta.counts.fixed', 'type' => 'integer', 'description' => 'Reports marked as fixed.'],
                ['name' => 'meta.counts.dismissed', 'type' => 'integer', 'description' => 'Reports dismissed.'],
                ['name' => 'meta.counts.all', 'type' => 'integer', 'description' => 'Every report.'],
            ],
            'title' => 'List agent feedback reports',
            'label' => 'List reports',
            'method' => 'GET',
            'path' => '/admin/agent-feedback',
            'description' => 'List the reports agents sent from every account, the newest first. The notes are left out of a list. Read one report to get them.',
            'body' => [$administrationNote],
            'rateLimit' => $rateLimit,
            'queryParams' => [
                ['name' => 'status', 'type' => 'string', 'required' => false, 'description' => 'Which reports to list: new, accepted, fixed, dismissed or all. Any other value is taken as new.', 'default' => 'new'],
                ['name' => 'q', 'type' => 'string', 'required' => false, 'description' => 'Keep only the reports whose title, tool, or user name or email holds this text.'],
                ['name' => 'kind', 'type' => 'string', 'required' => false, 'description' => 'Keep only one kind: bug, missing_tool, unclear_description or improvement.'],
                ['name' => 'severity', 'type' => 'string', 'required' => false, 'description' => 'Keep only one severity: blocks_task, needs_workaround or minor.'],
                ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Which page to answer with.', 'default' => '1'],
            ],
            'returns' => 'A page of report objects, how many there are in all, and in meta.counts how many each status holds.',
            'response' => [
                'data' => [$report],
                'meta' => [
                    'page' => 1,
                    'per_page' => 10,
                    'total' => 1,
                    'counts' => ['new' => 1, 'accepted' => 0, 'fixed' => 0, 'dismissed' => 0, 'all' => 1],
                ],
            ],
        ],
        [
            'id' => 'admin-agent-feedback-show',
            'returnFields' => ApiDocsFields::one('agent_feedback_report', 'report', [...$reportAttributes, ...$noteAttributes]),
            'title' => 'Get an agent feedback report',
            'label' => 'Get a report',
            'method' => 'GET',
            'path' => '/admin/agent-feedback/{agentFeedbackReport}',
            'examplePath' => '/admin/agent-feedback/'.$reportId,
            'description' => 'Return one report of any account, with the notes administrators left on it.',
            'body' => [$administrationNote],
            'rateLimit' => $rateLimit,
            'pathParams' => $reportIdentifier,
            'returns' => 'The report object. An id that matches no report answers 404.',
            'response' => ['data' => [...$report, 'attributes' => [...$report['attributes'], 'notes' => [$note]]]],
        ],
        [
            'id' => 'agent-feedback-create',
            'returnFields' => [
                ['name' => 'id', 'type' => 'uuid', 'description' => 'The id of the report.'],
                ['name' => 'status', 'type' => 'string', 'description' => 'Always new.'],
            ],
            'title' => 'Send agent feedback',
            'label' => 'Send a report',
            'method' => 'POST',
            'path' => '/agent-feedback',
            'description' => 'Tell the administrators of this instance about a problem with a tool. This is what the submit-feedback MCP tool calls. The person the agent works for never sees the report in Monica, only the call in their MCP history.',
            'body' => ['Send it at most once per task, and only when a tool got in the way. Paraphrase what the person asked, and use placeholders such as <contact> or <date> instead of names, dates and other details from the vault. Never include secrets.'],
            'rateLimit' => 'Ten requests per minute, per user.',
            'bodyParams' => [
                ['name' => 'kind', 'type' => 'string', 'required' => true, 'description' => 'bug when a tool failed or returned something wrong, missing_tool when no tool could do it, unclear_description when a description misled you, improvement for a suggestion.', 'example' => 'bug'],
                ['name' => 'severity', 'type' => 'string', 'required' => true, 'description' => 'blocks_task when the task could not be finished, needs_workaround when it was finished another way, minor when it was finished with friction.', 'example' => 'blocks_task'],
                ['name' => 'title', 'type' => 'string', 'required' => true, 'description' => 'One line naming the problem, up to 200 characters.', 'example' => 'Yearly reminders on 29 February are refused'],
                ['name' => 'tool', 'type' => 'string', 'required' => false, 'description' => 'The tool the report is about, or the name of the tool that is missing, up to 100 characters.', 'example' => 'create-contact-reminder'],
                ['name' => 'request', 'type' => 'string', 'required' => false, 'description' => 'What the person asked, paraphrased, up to 4,000 characters.'],
                ['name' => 'steps', 'type' => 'array', 'required' => false, 'description' => 'The tool calls tried, in order, at most 20. Each holds tool (required), arguments as a string, outcome (success or error, required) and result.', 'example' => $steps],
                ['name' => 'observed', 'type' => 'string', 'required' => true, 'description' => 'What happened, up to 4,000 characters. A placeholder such as test, todo or n/a answers 422.', 'example' => 'Any reminder on 29 February fails validation, with or without a year.'],
                ['name' => 'expected', 'type' => 'string', 'required' => true, 'description' => 'What should have happened, up to 4,000 characters.', 'example' => 'The reminder is created and falls on 28 February in other years.'],
                ['name' => 'proposed_fix', 'type' => 'string', 'required' => true, 'description' => 'The fix you suggest, up to 4,000 characters.', 'example' => 'Validate the day against a leap year when no year is given.'],
                ['name' => 'proposed_code', 'type' => 'string', 'required' => false, 'description' => 'Code for the fix, up to 8,000 characters.'],
                ['name' => 'model', 'type' => 'string', 'required' => false, 'description' => 'The model the agent runs on, up to 100 characters.'],
            ],
            'returns' => 'The id of the report and its status.',
            'responseStatus' => 201,
            'response' => ['id' => $reportId, 'status' => 'new'],
        ],
        [
            'id' => 'admin-agent-feedback-notes-create',
            'returnFields' => ApiDocsFields::one('agent_feedback_report', 'report', [...$reportAttributes, ...$noteAttributes]),
            'title' => 'Leave a note on an agent feedback report',
            'label' => 'Leave a note',
            'method' => 'POST',
            'path' => '/admin/agent-feedback/{agentFeedbackReport}/notes',
            'examplePath' => '/admin/agent-feedback/'.$reportId.'/notes',
            'description' => 'Write a note that only the administrators read. The status does not change.',
            'body' => [$administrationNote],
            'rateLimit' => $rateLimit,
            'pathParams' => $reportIdentifier,
            'bodyParams' => [
                ['name' => 'body', 'type' => 'string', 'required' => true, 'description' => 'What to write, up to 4,000 characters. A body left empty answers 422.', 'example' => 'Confirmed on a test account. Planned for the next release.'],
            ],
            'returns' => 'The report object with its notes.',
            'responseStatus' => 201,
            'response' => ['data' => [...$report, 'attributes' => [...$report['attributes'], 'notes' => [$note]]]],
        ],
        [
            'id' => 'admin-agent-feedback-status-update',
            'returnFields' => ApiDocsFields::one('agent_feedback_report', 'report', $reportAttributes),
            'title' => 'Change the status of an agent feedback report',
            'label' => 'Change the status',
            'method' => 'PUT',
            'path' => '/admin/agent-feedback/{agentFeedbackReport}/status',
            'examplePath' => '/admin/agent-feedback/'.$reportId.'/status',
            'description' => 'Move a report along. A new report can be accepted or dismissed, an accepted one fixed or dismissed, and a fixed or dismissed one reopened to new. The move is written to the administrator log.',
            'body' => [$administrationNote],
            'rateLimit' => $rateLimit,
            'pathParams' => $reportIdentifier,
            'bodyParams' => [
                ['name' => 'status', 'type' => 'string', 'required' => true, 'description' => 'The status to move to: new, accepted, fixed or dismissed. A move the current status does not allow answers 422 on status, naming both statuses.', 'example' => 'accepted'],
            ],
            'returns' => 'The report object as it stands now, without its notes. Read the report to get them.',
            'response' => ['data' => [...$report, 'attributes' => [...$report['attributes'], 'status' => 'accepted']]],
        ],
    ],
];
