<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();

$requestId = '7c1e4a92-5b3d-4f08-a6e1-93d2c8b05f17';
$messageId = 'e5a90b36-1c7d-4284-b9f3-0d6a82c471e9';
$replyId = '2f8d6c14-a093-4b75-8e21-c5b7094d3a68';
$attachmentId = 'b36f0d8e-47a1-4c92-95e8-1a2d7c63f0b4';

$attachment = [
    'id' => $attachmentId,
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

$supportRequest = [
    'type' => 'support_request',
    'id' => $requestId,
    'attributes' => [
        'reference' => 'MON-482913',
        'category' => 'question',
        'subject' => 'My conference room reminder never arrived',
        'status' => 'waiting_on_user',
        'status_label' => 'Reply from support',
        'opened_at' => 1791547200,
        'last_activity_at' => 1791550800,
        'solved_at' => null,
    ],
    'links' => [
        'self' => $base.'/support-requests/'.$requestId,
    ],
];

$supportRequestWithMessages = [
    ...$supportRequest,
    'attributes' => [
        ...$supportRequest['attributes'],
        'messages' => [$firstMessage, $reply],
    ],
];

$requestIdentifier = [
    ['name' => 'supportRequest', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of the request. One belonging to another account answers 404.'],
];

$messageAttributes = [
    ['name' => 'kind', 'type' => 'string', 'description' => 'message when the person who asked wrote it, reply when the support team did. An internal note is never shown here.'],
    ['name' => 'author_name', 'type' => 'string', 'description' => 'The name of the person who wrote it.'],
    ['name' => 'is_support_team', 'type' => 'boolean', 'description' => 'Whether the support team wrote it.'],
    ['name' => 'body', 'type' => 'string', 'description' => 'What was written, as plain text.'],
    ['name' => 'attachments', 'type' => 'array', 'description' => 'The files sent with it. An empty list when there are none.'],
    ['name' => 'attachments[]', 'type' => 'object', 'description' => 'One file.'],
    ['name' => 'attachments[].id', 'type' => 'uuid', 'description' => 'The identifier of the file, which the download address takes.'],
    ['name' => 'attachments[].name', 'type' => 'string', 'description' => 'The name the file had when it was sent.'],
    ['name' => 'attachments[].mime_type', 'type' => 'string', 'description' => 'The type of the file, such as image/png.'],
    ['name' => 'attachments[].size_in_bytes', 'type' => 'integer', 'description' => 'How large the file is, in bytes.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When it was written, as a count of seconds since 1970.'],
];

$requestAttributes = [
    ['name' => 'reference', 'type' => 'string', 'description' => 'The reference to quote when writing to the support team, such as MON-482913.'],
    ['name' => 'category', 'type' => 'string', 'description' => 'What the request is about: feature, billing, email, question or technical.'],
    ['name' => 'subject', 'type' => 'string', 'description' => 'The title the request was opened with.'],
    ['name' => 'status', 'type' => 'string', 'description' => 'Where the request stands: to_handle when the support team has yet to answer, waiting_on_user when it has and awaits you, or solved.'],
    ['name' => 'status_label', 'type' => 'string', 'description' => 'The status as the application words it for the person who asked, in the language of the account.'],
    ['name' => 'opened_at', 'type' => 'timestamp', 'description' => 'When the request was opened, as a count of seconds since 1970.'],
    ['name' => 'last_activity_at', 'type' => 'timestamp', 'description' => 'When anybody last wrote in it, the person who asked or the support team, as a count of seconds since 1970. A change of status alone does not move it.'],
    ['name' => 'solved_at', 'type' => 'timestamp', 'description' => 'When it was marked as solved, as a count of seconds since 1970, or null while it is open.'],
];

$requestWithMessagesAttributes = [
    ...$requestAttributes,
    ['name' => 'messages', 'type' => 'array', 'description' => 'The whole thread, the oldest first, without the internal notes of the support team.'],
    ['name' => 'messages[]', 'type' => 'object', 'description' => 'One message of the thread.'],
    ['name' => 'messages[].type', 'type' => 'string', 'description' => 'Always support_message.'],
    ['name' => 'messages[].id', 'type' => 'string', 'description' => 'The identifier of the message.'],
    ['name' => 'messages[].attributes', 'type' => 'object', 'description' => 'Everything the message holds.'],
    ...array_map(fn (array $field): array => [...$field, 'name' => 'messages[].attributes.'.$field['name']], $messageAttributes),
];

$messageFields = fn (string $prefix): array => array_map(fn (array $field): array => [...$field, 'name' => $prefix.$field['name']], $messageAttributes);

$messageResponseFields = [
    ['name' => 'data', 'type' => 'object', 'description' => 'The message.'],
    ['name' => 'data.type', 'type' => 'string', 'description' => 'Always support_message.'],
    ['name' => 'data.id', 'type' => 'string', 'description' => 'The identifier of the message.'],
    ['name' => 'data.attributes', 'type' => 'object', 'description' => 'Everything the message holds.'],
    ...$messageFields('data.attributes.'),
];

$fileNote = 'Files are sent as multipart form data, in a field called files that is repeated once per file. At most 5 files, each at most 10 MB, of the types jpg, jpeg, png, gif, webp, heic, pdf, txt and log. A file that breaks any of these answers 422 on files or on files.0, files.1 and so on, and nothing is kept.';

$rateLimit = 'Sixty requests per minute, per user, shared by every key and device.';

return [
    'name' => 'Support requests',
    'description' => 'Ask the support team a question and follow the conversation. A request holds a thread of messages between you and the team, and moves between three statuses: to_handle while the team has yet to answer, waiting_on_user once it has, and solved. Writing to a request always puts it back in to_handle.',
    'sections' => [
        [
            'id' => 'support-requests-index',
            'support' => true,
            'returnFields' => ApiDocsFields::page('support_request', 'support request', 'support requests', $requestAttributes),
            'title' => 'List support requests',
            'label' => 'List requests',
            'method' => 'GET',
            'path' => '/support-requests',
            'description' => 'List the support requests of your account, the most recently active first. The messages are left out of a list. Read one request to get its thread.',
            'rateLimit' => $rateLimit,
            'queryParams' => [
                ['name' => 'status', 'type' => 'string', 'required' => false, 'description' => 'Keep only the requests in this status: to_handle, waiting_on_user or solved. Any other value answers 422 on status.'],
                ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Which page to answer with.', 'default' => '1'],
                ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'How many records one page holds, between 1 and 100. A number outside the range is brought back into it rather than refused.', 'default' => '25'],
            ],
            'returns' => 'A page of support request objects, and how many there are in all. Only the requests of your account are listed.',
            'response' => [
                'data' => [$supportRequest],
                'meta' => ['page' => 1, 'per_page' => 25, 'total' => 1],
            ],
        ],
        [
            'id' => 'support-requests-show',
            'support' => true,
            'returnFields' => ApiDocsFields::one('support_request', 'support request', $requestWithMessagesAttributes),
            'title' => 'Get a support request',
            'label' => 'Get a request',
            'method' => 'GET',
            'path' => '/support-requests/{supportRequest}',
            'examplePath' => '/support-requests/'.$requestId,
            'description' => 'Return one support request with its whole thread, the oldest message first. The internal notes of the support team are never part of it.',
            'rateLimit' => $rateLimit,
            'pathParams' => $requestIdentifier,
            'returns' => 'The support request object, with its messages. A request of another account answers 404.',
            'response' => ['data' => $supportRequestWithMessages],
        ],
        [
            'id' => 'support-requests-create',
            'support' => true,
            'returnFields' => ApiDocsFields::one('support_request', 'support request', $requestWithMessagesAttributes),
            'title' => 'Open a support request',
            'label' => 'Open a request',
            'method' => 'POST',
            'path' => '/support-requests',
            'description' => 'Open a request with the support team. The body becomes the first message of the thread, and the request starts in to_handle.',
            'body' => [
                $fileNote,
                'An instance that has no active administrator has nobody to answer, so it answers 404 here.',
            ],
            'rateLimit' => $rateLimit,
            'bodyParams' => [
                ['name' => 'category', 'type' => 'string', 'required' => true, 'description' => 'What the request is about: feature, billing, email, question or technical.', 'example' => 'question'],
                ['name' => 'subject', 'type' => 'string', 'required' => true, 'description' => 'The title of the request, up to 255 characters.', 'example' => 'My conference room reminder never arrived'],
                ['name' => 'body', 'type' => 'string', 'required' => true, 'description' => 'What you want to say, between 10 and 20,000 characters.', 'example' => 'I booked the conference room for the quarterly meeting, but the reminder never arrived. Dwight says it is a conspiracy.'],
                ['name' => 'files', 'type' => 'file', 'required' => false, 'description' => 'Files to send with the first message. Repeat the field once per file, up to 5.'],
            ],
            'returns' => 'The support request object that was opened, with its first message.',
            'responseStatus' => 201,
            'response' => ['data' => [
                ...$supportRequest,
                'attributes' => [
                    ...$supportRequest['attributes'],
                    'status' => 'to_handle',
                    'status_label' => 'Waiting for support',
                    'last_activity_at' => 1791547200,
                    'messages' => [$firstMessage],
                ],
            ]],
        ],
        [
            'id' => 'support-request-messages-create',
            'support' => true,
            'returnFields' => $messageResponseFields,
            'title' => 'Write to the support team',
            'label' => 'Write a message',
            'method' => 'POST',
            'path' => '/support-requests/{supportRequest}/messages',
            'examplePath' => '/support-requests/'.$requestId.'/messages',
            'description' => 'Add a message to a request you opened. Whatever its status, including solved, the request goes back to to_handle.',
            'body' => [$fileNote],
            'rateLimit' => $rateLimit,
            'pathParams' => $requestIdentifier,
            'bodyParams' => [
                ['name' => 'body', 'type' => 'string', 'required' => true, 'description' => 'What you want to say, up to 20,000 characters. A body left empty answers 422.', 'example' => 'Thank you, Jim. Could you also move the one for the Scranton Business Park meeting?'],
                ['name' => 'files', 'type' => 'file', 'required' => false, 'description' => 'Files to send with the message. Repeat the field once per file, up to 5.'],
            ],
            'returns' => 'The message object that was written. A request of another account answers 404.',
            'responseStatus' => 201,
            'response' => ['data' => [
                ...$firstMessage,
                'attributes' => [
                    ...$firstMessage['attributes'],
                    'body' => 'Thank you, Jim. Could you also move the one for the Scranton Business Park meeting?',
                    'created_at' => 1791554400,
                ],
            ]],
        ],
        [
            'id' => 'support-request-solution-create',
            'support' => true,
            'returnFields' => ApiDocsFields::one('support_request', 'support request', $requestAttributes),
            'title' => 'Mark a support request as solved',
            'label' => 'Mark as solved',
            'method' => 'POST',
            'path' => '/support-requests/{supportRequest}/solution',
            'examplePath' => '/support-requests/'.$requestId.'/solution',
            'description' => 'Tell the support team the request needs nothing more. Its status becomes solved. Writing to it again reopens it.',
            'body' => ['A request that is already solved answers 422 on status with the message This request is already solved.'],
            'rateLimit' => $rateLimit,
            'pathParams' => $requestIdentifier,
            'returns' => 'The support request object as it stands now, without its messages. A request of another account answers 404.',
            'response' => ['data' => [
                ...$supportRequest,
                'attributes' => [
                    ...$supportRequest['attributes'],
                    'status' => 'solved',
                    'status_label' => 'Solved',
                    'last_activity_at' => 1791558000,
                    'solved_at' => 1791558000,
                ],
            ]],
        ],
        [
            'id' => 'support-request-attachments-content-show',
            'support' => true,
            'title' => 'Download a file of a support request',
            'label' => 'Download a file',
            'method' => 'GET',
            'path' => '/support-requests/{supportRequest}/attachments/{attachment}/content',
            'examplePath' => '/support-requests/'.$requestId.'/attachments/'.$attachmentId.'/content',
            'description' => 'Return a file sent with a message of the request, byte for byte.',
            'body' => ['This endpoint does not answer in JSON. The body is the file, sent as a download with the type and the name it was uploaded with. The identifier of the file is the id found in the attachments of a message.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [
                ...$requestIdentifier,
                ['name' => 'attachment', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of the file. One sent with an internal note, one belonging to another request, or one whose content is no longer stored answers 404.'],
            ],
            'returns' => 'The file. A request of another account answers 404.',
            'responseMediaType' => 'application/octet-stream',
            'responseIsFile' => true,
        ],
    ],
];
