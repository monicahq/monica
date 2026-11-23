<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();

$requestId = '7c1e4a92-5b3d-4f08-a6e1-93d2c8b05f17';
$messageId = 'e5a90b36-1c7d-4284-b9f3-0d6a82c471e9';
$replyId = '2f8d6c14-a093-4b75-8e21-c5b7094d3a68';
$noteId = '8a41c7e5-d209-4b3f-b6a8-5e90f13d7c24';

$attachment = [
    'id' => 'b36f0d8e-47a1-4c92-95e8-1a2d7c63f0b4',
    'name' => 'conference-room-booking.png',
    'mime_type' => 'image/png',
    'size_in_bytes' => 245760,
];

$firstMessage = [
    'type' => 'support_message',
    'id' => $messageId,
    'attributes' => [
        'kind' => 'message',
        'author_name' => 'Michael Scott',
        'is_support_team' => false,
        'body' => 'I booked the conference room for the quarterly meeting, but the reminder never arrived. Dwight says it is a conspiracy.',
        'attachments' => [$attachment],
        'created_at' => 1791547200,
    ],
];

$reply = [
    'type' => 'support_message',
    'id' => $replyId,
    'attributes' => [
        'kind' => 'reply',
        'author_name' => 'Jim Halpert',
        'is_support_team' => true,
        'body' => 'Hi Michael. The reminder was set for a time that had already passed, so it was skipped. We have moved it to tomorrow at nine.',
        'attachments' => [],
        'created_at' => 1791550800,
    ],
];

$note = [
    'type' => 'support_message',
    'id' => $noteId,
    'attributes' => [
        'kind' => 'note',
        'author_name' => 'Jim Halpert',
        'is_support_team' => true,
        'body' => 'Same account as the ticket about the birthday reminders. Check with Pam before closing.',
        'attachments' => [],
        'created_at' => 1791551400,
    ],
];

$context = [
    ['label' => 'Account', 'value' => '#12'],
    ['label' => 'Access', 'value' => 'Free plan'],
    ['label' => 'Language', 'value' => 'English'],
    ['label' => 'Version', 'value' => '5.0.0'],
    ['label' => 'Content', 'value' => '1 vault, 24 contacts'],
    ['label' => 'Signed up', 'value' => 'March 4, 2026'],
];

$adminRequest = [
    'type' => 'support_request',
    'id' => $requestId,
    'attributes' => [
        'reference' => 'MON-482913',
        'account_id' => 12,
        'user' => ['name' => 'Michael Scott', 'email' => 'michael.scott@dundermifflin.test'],
        'category' => 'question',
        'subject' => 'My conference room reminder never arrived',
        'status' => 'waiting_on_user',
        'status_label' => 'Waiting on the user',
        'application_version' => '5.0.0',
        'locale' => 'en',
        'opened_at' => 1791547200,
        'last_activity_at' => 1791550800,
        'solved_at' => null,
    ],
    'links' => [
        'self' => $base.'/admin/support-requests/'.$requestId,
    ],
];

$adminRequestWhole = [
    ...$adminRequest,
    'attributes' => [
        ...$adminRequest['attributes'],
        'messages' => [$firstMessage, $reply, $note],
        'context' => $context,
        'status_changes' => [
            ['from' => null, 'to' => 'to_handle', 'by' => 'Michael Scott', 'at' => 1791547200],
            ['from' => 'to_handle', 'to' => 'waiting_on_user', 'by' => 'Jim Halpert', 'at' => 1791550800],
        ],
    ],
];

$requestIdentifier = [
    ['name' => 'supportRequest', 'type' => 'uuid', 'required' => true, 'description' => 'The id of the request, of any account on the instance.'],
];

$administrationNote = 'Only an administrator of this instance can use these endpoints, and only with an API key that carries the administration scope by name. Anybody else, including an administrator whose key does not carry it, gets 404 as though the endpoint did not exist.';

$rateLimit = 'Sixty requests per minute, per user, shared by every key and device.';

$requestAttributes = [
    ['name' => 'reference', 'type' => 'string', 'description' => 'The reference the person quotes when writing to the support team, such as MON-482913.'],
    ['name' => 'account_id', 'type' => 'integer', 'description' => 'The account the request was opened from.'],
    ['name' => 'user', 'type' => 'object', 'description' => 'Who asked.'],
    ['name' => 'user.name', 'type' => 'string', 'description' => 'The name of the person who opened the request, or null once their user is deleted.'],
    ['name' => 'user.email', 'type' => 'string', 'description' => 'Where replies are emailed, or null once their user is deleted.'],
    ['name' => 'category', 'type' => 'string', 'description' => 'What the request is about: feature, billing, email, question or technical.'],
    ['name' => 'subject', 'type' => 'string', 'description' => 'The title the request was opened with.'],
    ['name' => 'status', 'type' => 'string', 'description' => 'to_handle when the person is waiting for an answer, waiting_on_user when the team is, or solved.'],
    ['name' => 'status_label', 'type' => 'string', 'description' => 'The status as the administrators read it, in the language of the signed in administrator.'],
    ['name' => 'application_version', 'type' => 'string', 'description' => 'The version of the application the request was opened from.'],
    ['name' => 'locale', 'type' => 'string', 'description' => 'The language the person was using when they opened it.'],
    ['name' => 'opened_at', 'type' => 'timestamp', 'description' => 'When the request was opened, as a count of seconds since 1970.'],
    ['name' => 'last_activity_at', 'type' => 'timestamp', 'description' => 'When anybody last wrote in it, the person who asked or the support team, as a count of seconds since 1970. A change of status alone does not move it.'],
    ['name' => 'solved_at', 'type' => 'timestamp', 'description' => 'When it was marked as solved, as a count of seconds since 1970, or null while it is open.'],
];

$messageAttributes = [
    ['name' => 'kind', 'type' => 'string', 'description' => 'message when the person wrote it, reply when an administrator answered them, note when an administrator wrote it for the team alone.'],
    ['name' => 'author_name', 'type' => 'string', 'description' => 'The name of the person who wrote it.'],
    ['name' => 'is_support_team', 'type' => 'boolean', 'description' => 'Whether an administrator wrote it.'],
    ['name' => 'body', 'type' => 'string', 'description' => 'What was written, as plain text.'],
    ['name' => 'attachments', 'type' => 'array', 'description' => 'The files sent with it. An empty list when there are none, which is always the case for a reply or a note written through this API.'],
    ['name' => 'attachments[]', 'type' => 'object', 'description' => 'One file.'],
    ['name' => 'attachments[].id', 'type' => 'uuid', 'description' => 'The id of the file.'],
    ['name' => 'attachments[].name', 'type' => 'string', 'description' => 'The name the file had when it was sent.'],
    ['name' => 'attachments[].mime_type', 'type' => 'string', 'description' => 'The type of the file, such as image/png.'],
    ['name' => 'attachments[].size_in_bytes', 'type' => 'integer', 'description' => 'How large the file is, in bytes.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When it was written, as a count of seconds since 1970.'],
];

$withPrefix = fn (string $prefix, array $fields): array => array_map(fn (array $field): array => [...$field, 'name' => $prefix.$field['name']], $fields);

$threadAttributes = [
    ['name' => 'messages', 'type' => 'array', 'description' => 'The whole thread, the oldest first, with the internal notes.'],
    ['name' => 'messages[]', 'type' => 'object', 'description' => 'One message of the thread.'],
    ['name' => 'messages[].type', 'type' => 'string', 'description' => 'Always support_message.'],
    ['name' => 'messages[].id', 'type' => 'string', 'description' => 'The id of the message.'],
    ['name' => 'messages[].attributes', 'type' => 'object', 'description' => 'Everything the message holds.'],
    ...$withPrefix('messages[].attributes.', $messageAttributes),
    ['name' => 'context', 'type' => 'array', 'description' => 'A few facts about the account, to answer with.'],
    ['name' => 'context[]', 'type' => 'object', 'description' => 'One fact.'],
    ['name' => 'context[].label', 'type' => 'string', 'description' => 'What the fact is about, such as Version.'],
    ['name' => 'context[].value', 'type' => 'string', 'description' => 'The fact.'],
];

$historyAttributes = [
    ['name' => 'status_changes', 'type' => 'array', 'description' => 'How the status moved, the oldest first.'],
    ['name' => 'status_changes[]', 'type' => 'object', 'description' => 'One change of status.'],
    ['name' => 'status_changes[].from', 'type' => 'string', 'description' => 'The status it left, or null for the change that opened the request.'],
    ['name' => 'status_changes[].to', 'type' => 'string', 'description' => 'The status it took.'],
    ['name' => 'status_changes[].by', 'type' => 'string', 'description' => 'The name of who changed it.'],
    ['name' => 'status_changes[].at', 'type' => 'timestamp', 'description' => 'When, as a count of seconds since 1970.'],
];

$messageResponseFields = [
    ['name' => 'data', 'type' => 'object', 'description' => 'The message.'],
    ['name' => 'data.type', 'type' => 'string', 'description' => 'Always support_message.'],
    ['name' => 'data.id', 'type' => 'string', 'description' => 'The id of the message.'],
    ['name' => 'data.attributes', 'type' => 'object', 'description' => 'Everything the message holds.'],
    ...$withPrefix('data.attributes.', array_values(array_filter($messageAttributes, fn (array $field): bool => ! str_starts_with($field['name'], 'attachments[')))),
];

return [
    'name' => 'Support administration',
    'description' => 'Work through the support queue of the whole instance: read the requests of every account, answer them, leave notes for the team and move them between statuses. '.$administrationNote,
    'sections' => [
        [
            'id' => 'admin-support-requests-index',
            'support' => true,
            'returnFields' => [
                ...ApiDocsFields::page('support_request', 'support request', 'support requests', $requestAttributes),
                ['name' => 'meta.counts', 'type' => 'object', 'description' => 'How many requests each view holds under the filters you sent, apart from the view itself.'],
                ['name' => 'meta.counts.to_handle', 'type' => 'integer', 'description' => 'Requests waiting for an answer.'],
                ['name' => 'meta.counts.waiting_on_user', 'type' => 'integer', 'description' => 'Requests answered and waiting on the person.'],
                ['name' => 'meta.counts.solved', 'type' => 'integer', 'description' => 'Requests marked as solved.'],
                ['name' => 'meta.counts.all', 'type' => 'integer', 'description' => 'Every request.'],
            ],
            'title' => 'List support requests of the instance',
            'label' => 'List requests',
            'method' => 'GET',
            'path' => '/admin/support-requests',
            'description' => 'List the support requests of every account, the oldest first, 50 to a page. The thread, the account facts and the status changes are left out of a list. Read one request to get them.',
            'body' => [$administrationNote],
            'rateLimit' => $rateLimit,
            'queryParams' => [
                ['name' => 'view', 'type' => 'string', 'required' => false, 'description' => 'Which requests to list: to_handle, waiting_on_user, solved or all. Any other value is taken as to_handle.', 'default' => 'to_handle', 'example' => 'all'],
                ['name' => 'q', 'type' => 'string', 'required' => false, 'description' => 'Keep only the requests that mention this text.', 'example' => 'Dundie'],
                ['name' => 'category', 'type' => 'string', 'required' => false, 'description' => 'Keep only one category: feature, billing, email, question or technical.', 'example' => 'billing'],
                ['name' => 'user', 'type' => 'integer', 'required' => false, 'description' => 'Keep only the requests of the user with this id.'],
                ['name' => 'age', 'type' => 'string', 'required' => false, 'description' => 'Keep only the requests of one age: day for under 24 hours, week for 1 to 7 days, old for older than 7 days.', 'example' => 'week'],
                ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Which page to answer with.', 'default' => '1', 'example' => 2],
            ],
            'returns' => 'A page of support request objects, how many there are in all, and in meta.counts how many each view holds.',
            'response' => [
                'data' => [$adminRequest],
                'meta' => [
                    'page' => 1,
                    'per_page' => 50,
                    'total' => 1,
                    'counts' => ['to_handle' => 0, 'waiting_on_user' => 1, 'solved' => 0, 'all' => 1],
                ],
            ],
        ],
        [
            'id' => 'admin-support-requests-show',
            'support' => true,
            'returnFields' => ApiDocsFields::one('support_request', 'support request', [...$requestAttributes, ...$threadAttributes, ...$historyAttributes]),
            'title' => 'Get a support request of the instance',
            'label' => 'Get a request',
            'method' => 'GET',
            'path' => '/admin/support-requests/{supportRequest}',
            'examplePath' => '/admin/support-requests/'.$requestId,
            'description' => 'Return one support request of any account, with the whole thread including the internal notes, a few facts about the account, and how the status moved.',
            'body' => [$administrationNote],
            'rateLimit' => $rateLimit,
            'pathParams' => $requestIdentifier,
            'returns' => 'The support request object. An id that matches no request answers 404.',
            'response' => ['data' => $adminRequestWhole],
        ],
        [
            'id' => 'admin-support-request-replies-create',
            'support' => true,
            'returnFields' => $messageResponseFields,
            'title' => 'Answer a support request',
            'label' => 'Answer a request',
            'method' => 'POST',
            'path' => '/admin/support-requests/{supportRequest}/replies',
            'examplePath' => '/admin/support-requests/'.$requestId.'/replies',
            'description' => 'Write an answer to the person who opened the request, and email it to them. The request takes the status you name, which is waiting_on_user when you name none.',
            'body' => [$administrationNote],
            'rateLimit' => $rateLimit,
            'pathParams' => $requestIdentifier,
            'bodyParams' => [
                ['name' => 'body', 'type' => 'string', 'required' => true, 'description' => 'What to say, up to 20,000 characters. A body left empty answers 422.', 'example' => 'Hi Michael. The reminder was set for a time that had already passed, so it was skipped. We have moved it to tomorrow at nine.'],
                ['name' => 'status', 'type' => 'string', 'required' => false, 'description' => 'The status the request takes once answered: to_handle, waiting_on_user or solved. Any other value answers 422 on status.', 'default' => 'waiting_on_user', 'example' => 'waiting_on_user'],
            ],
            'returns' => 'The message object that was written.',
            'responseStatus' => 201,
            'response' => ['data' => $reply],
        ],
        [
            'id' => 'admin-support-request-notes-create',
            'support' => true,
            'returnFields' => $messageResponseFields,
            'title' => 'Leave a note on a support request',
            'label' => 'Leave a note',
            'method' => 'POST',
            'path' => '/admin/support-requests/{supportRequest}/notes',
            'examplePath' => '/admin/support-requests/'.$requestId.'/notes',
            'description' => 'Write a note that only the administrators read. It is never emailed and never shown to the person who asked.',
            'body' => [$administrationNote],
            'rateLimit' => $rateLimit,
            'pathParams' => $requestIdentifier,
            'bodyParams' => [
                ['name' => 'body', 'type' => 'string', 'required' => true, 'description' => 'What to write, up to 20,000 characters. A body left empty answers 422.', 'example' => 'Same account as the ticket about the birthday reminders. Check with Pam before closing.'],
            ],
            'returns' => 'The message object that was written, with a kind of note.',
            'responseStatus' => 201,
            'response' => ['data' => $note],
        ],
        [
            'id' => 'admin-support-request-status-update',
            'support' => true,
            'returnFields' => ApiDocsFields::one('support_request', 'support request', $requestAttributes),
            'title' => 'Change the status of a support request',
            'label' => 'Change the status',
            'method' => 'PUT',
            'path' => '/admin/support-requests/{supportRequest}/status',
            'examplePath' => '/admin/support-requests/'.$requestId.'/status',
            'description' => 'Move a request to another status without writing to the person.',
            'body' => [$administrationNote],
            'rateLimit' => $rateLimit,
            'pathParams' => $requestIdentifier,
            'bodyParams' => [
                ['name' => 'status', 'type' => 'string', 'required' => true, 'description' => 'The status to move to: to_handle, waiting_on_user or solved. Any other value answers 422 on status.', 'example' => 'solved'],
            ],
            'returns' => 'The support request object as it stands now, without its messages and its status changes. Read the request to get them.',
            'response' => ['data' => [
                ...$adminRequest,
                'attributes' => [
                    ...$adminRequest['attributes'],
                    'status' => 'solved',
                    'status_label' => 'Solved',
                    'solved_at' => 1791554400,
                    'last_activity_at' => 1791554400,
                ],
            ]],
        ],
    ],
];
