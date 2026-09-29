<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();

$unread = [
    'type' => 'notification',
    'id' => '9c7f2a1e-4b6d-4f0a-9f2b-1d3c5e7a9b11',
    'attributes' => [
        'notification_type' => 'reminder_due',
        'channel' => 'in_app',
        'title' => 'Birthday, Michael Scott',
        'body' => 'It is in seven days.',
        'scheduled_for' => 1756339200,
        'sent_at' => 1756339210,
        'read_at' => null,
        'failure_reason' => null,
        'created_at' => 1756339200,
    ],
    'links' => [
        'self' => $base.'/notifications/9c7f2a1e-4b6d-4f0a-9f2b-1d3c5e7a9b11',
    ],
];

$read = $unread;
$read['id'] = '2f8e6c4a-7d1b-4e93-8a05-6c9f1b2d3e44';
$read['attributes']['channel'] = 'email';
$read['attributes']['title'] = 'Anniversary, Jim Halpert';
$read['attributes']['body'] = 'It is today.';
$read['attributes']['read_at'] = 1756342800;
$read['links']['self'] = $base.'/notifications/2f8e6c4a-7d1b-4e93-8a05-6c9f1b2d3e44';

$notificationAttributes = [
    ['name' => 'notification_type', 'type' => 'string', 'description' => 'What the notification is about, as a key such as reminder_due or digest. The set grows as the product finds new things to say, so match the keys you care about and let an unfamiliar one through rather than treating it as an error.'],
    ['name' => 'channel', 'type' => 'string', 'description' => 'Which way it went out: in_app, email, slack, telegram or sms. One notification is written per channel, so a reminder that went out two ways is two records here, alike in everything but this field.'],
    ['name' => 'title', 'type' => 'string', 'description' => 'The one line it leads with, up to 255 characters. For a reminder it is the name of the reminder.'],
    ['name' => 'body', 'type' => 'string', 'description' => 'What it says under the title, of no fixed length, written in the language the account was set to at the moment it was made rather than the one you are asking in.'],
    ['name' => 'scheduled_for', 'type' => 'timestamp', 'description' => 'When it was due, as a count of seconds since 1970. Quiet hours can hold a notification back, so this can be earlier than sent_at by hours.'],
    ['name' => 'sent_at', 'type' => 'timestamp', 'description' => 'When it went out, as a count of seconds since 1970, or null when it has not gone out yet.'],
    ['name' => 'read_at', 'type' => 'timestamp', 'description' => 'When it was first read, as a count of seconds since 1970, or null when it has not been. Reading something twice keeps the first moment.'],
    ['name' => 'failure_reason', 'type' => 'string', 'description' => 'What the channel said when it refused, up to 255 characters, or null when nothing refused. A notification that failed keeps its record and its reason rather than disappearing.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the record was written, as a count of seconds since 1970.'],
];

return [
    'name' => 'Notifications',
    'description' => 'What the product has told the account holder, and whether they have read it.',
    'sections' => [
        [
            'id' => 'notifications-index',
            'returnFields' => ApiDocsFields::page('notification', 'notification', 'notifications', $notificationAttributes),
            'title' => 'List notifications',
            'label' => 'List notifications',
            'method' => 'GET',
            'path' => '/notifications',
            'description' => 'List what the application has told you, newest first.',
            'body' => [
                'These are yours rather than the account\'s: the list is scoped to whoever the token belongs to. One record is written per channel, so a reminder that went out both in the application and by email appears twice, once for each.',
                'Notifications are removed automatically once they pass the retention period this instance is set to. That is a stated policy rather than a silent loss, and what a reminder itself records outlives it: the deadline still says somebody was told.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'queryParams' => [
                [
                    'name' => 'read',
                    'type' => 'string',
                    'required' => false,
                    'description' => 'Narrow the list to what has been read, with true, or to what has not, with false. Anything else is ignored and the whole list comes back.',
                    'default' => 'everything',
                ],
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
                    'description' => 'How many notifications one page holds, between 1 and 100. A number outside the range is brought back into it rather than refused.',
                    'default' => '25',
                ],
            ],
            'returns' => 'A page of notifications, and how many there are in all. An account that has never been told anything gets an empty list rather than an error.',
            'response' => [
                'data' => [$unread, $read],
                'meta' => [
                    'page' => 1,
                    'per_page' => 25,
                    'total' => 2,
                ],
            ],
        ],
        [
            'id' => 'notifications-show',
            'returnFields' => ApiDocsFields::one('notification', 'notification', $notificationAttributes),
            'title' => 'Get a notification',
            'label' => 'Get a notification',
            'method' => 'GET',
            'path' => '/notifications/{notification}',
            'examplePath' => '/notifications/9c7f2a1e-4b6d-4f0a-9f2b-1d3c5e7a9b11',
            'description' => 'Retrieve one notification by its address.',
            'body' => [
                'Reading a notification through this endpoint does not mark it as read. Say so on purpose with the endpoint below, so that fetching a record and acting on it stay two separate things.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => [
                [
                    'name' => 'notification',
                    'type' => 'uuid',
                    'required' => true,
                    'description' => 'The address of the notification.',
                ],
            ],
            'returns' => 'One notification. A notification belonging to another account answers 404, and so does an address that matches nothing, deliberately: the two cannot be told apart.',
            'response' => ['data' => $unread],
        ],
        [
            'id' => 'notifications-read-update',
            'returnFields' => ApiDocsFields::one('notification', 'notification', $notificationAttributes),
            'title' => 'Mark a notification as read',
            'label' => 'Mark as read',
            'method' => 'PUT',
            'path' => '/notifications/{notification}/read',
            'examplePath' => '/notifications/9c7f2a1e-4b6d-4f0a-9f2b-1d3c5e7a9b11/read',
            'description' => 'Record that you have read one notification.',
            'body' => [
                'Marking something read twice keeps the first moment, because that is when it was read. There is no way to mark one unread again: this records something that happened rather than setting a flag.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => [
                [
                    'name' => 'notification',
                    'type' => 'uuid',
                    'required' => true,
                    'description' => 'The address of the notification.',
                ],
            ],
            'returns' => 'The notification as it now stands, carrying the moment it was read.',
            'response' => ['data' => array_replace_recursive($unread, ['attributes' => ['read_at' => 1756342800]])],
        ],
        [
            'id' => 'notifications-read-all-update',
            'returnFields' => [
                ['name' => 'data', 'type' => 'object', 'description' => 'What this marked.'],
                ['name' => 'data.marked', 'type' => 'integer', 'description' => 'How many notifications went from unread to read. Zero when there was nothing waiting.'],
            ],
            'title' => 'Mark every notification as read',
            'label' => 'Mark every one as read',
            'method' => 'PUT',
            'path' => '/notifications/read',
            'description' => 'Record that you have read everything waiting for you.',
            'body' => [
                'Everything unread that belongs to you is marked at once. Nothing belonging to anybody else is touched, and nothing already read has its moment changed.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'returns' => 'How many notifications this marked. Nothing waiting answers with zero rather than an error.',
            'response' => ['data' => ['marked' => 2]],
        ],
    ],
];
