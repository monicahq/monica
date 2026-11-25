<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();
$application = rtrim((string) config('app.url'), '/');

$rateLimit = 'Sixty requests per minute, per user, shared by every key and device.';

$conferenceRoomId = '01M7NBFWK5XNH908RFDGEAJXKN';
$faxMachineId = '01M7NBFWK7BEESY8WABBMXF3E6';
$commentId = '6b2f9d41-8c37-4e05-a1d6-3f9e7c2b5a84';
$anonymousCommentId = 'e1a7c3d9-4b52-4f86-9c0e-7d3a5f1b8e26';

$conferenceRoom = [
    'type' => 'changelog_entry',
    'id' => $conferenceRoomId,
    'attributes' => [
        'entry_type' => 'feature',
        'area' => 'Contacts',
        'title' => 'Book the conference room from a contact',
        'body' => 'Michael can now book the conference room **straight** from a contact page.',
        'body_html' => '<p>Michael can now book the conference room <strong>straight</strong> from a contact page.</p>',
        'link' => "{$application}/vaults",
        'screenshots' => [
            [
                'position' => 1,
                'url' => "{$application}/images/changelog/{$conferenceRoomId}-1.webp",
                'description' => 'The contact page with the conference room booked',
                'width' => 1600,
                'height' => 1000,
            ],
        ],
        'day' => '2026-11-16',
        'is_seen' => false,
        'my_vote' => 'up',
        'comment_count' => 2,
        'published_at' => 1794819600,
    ],
    'links' => [
        'comments' => "{$base}/changelog-entries/{$conferenceRoomId}/comments",
        'web' => "{$application}/changelog",
    ],
];

$faxMachine = [
    'type' => 'changelog_entry',
    'id' => $faxMachineId,
    'attributes' => [
        'entry_type' => 'fix',
        'area' => 'Contacts',
        'title' => 'The fax machine no longer eats long notes',
        'body' => 'A note longer than 10,000 characters lost its last paragraph.',
        'body_html' => '<p>A note longer than 10,000 characters lost its last paragraph.</p>',
        'link' => null,
        'screenshots' => [],
        'day' => '2026-11-16',
        'is_seen' => true,
        'my_vote' => null,
        'comment_count' => 0,
        'published_at' => 1794816000,
    ],
    'links' => [
        'comments' => "{$base}/changelog-entries/{$faxMachineId}/comments",
        'web' => "{$application}/changelog",
    ],
];

$comment = [
    'type' => 'changelog_comment',
    'id' => $commentId,
    'attributes' => [
        'author_name' => 'Dwight Schrute',
        'is_mine' => true,
        'is_private' => false,
        'is_anonymous' => false,
        'body' => 'The conference room should require a key card. Security is not a game.',
        'replies' => [
            [
                'author_name' => 'Michael Scott',
                'body' => 'Noted, Dwight. The key card stays in my office.',
                'created_at' => 1794830400,
            ],
        ],
        'created_at' => 1794823200,
        'updated_at' => 1794823200,
    ],
];

$anonymousComment = [
    'type' => 'changelog_comment',
    'id' => $anonymousCommentId,
    'attributes' => [
        'author_name' => 'Anonymous',
        'is_mine' => false,
        'is_private' => false,
        'is_anonymous' => true,
        'body' => 'Can we book it for the Dundies?',
        'replies' => [],
        'created_at' => 1794826800,
        'updated_at' => 1794826800,
    ],
];

$entryAttributes = [
    ['name' => 'entry_type', 'type' => 'string', 'description' => 'feature, improvement or fix.'],
    ['name' => 'area', 'type' => 'string', 'description' => 'The part of the product the change touches, in your language, or null.'],
    ['name' => 'title', 'type' => 'string', 'description' => 'What changed, in one line, in your language.'],
    ['name' => 'body', 'type' => 'string', 'description' => 'The explanation, as Markdown, in your language.'],
    ['name' => 'body_html', 'type' => 'string', 'description' => 'The same explanation, turned into HTML.'],
    ['name' => 'link', 'type' => 'string', 'description' => 'The absolute address of the screen the change concerns, or null.'],
    ['name' => 'screenshots', 'type' => 'array', 'description' => 'Screenshots of the change, in the order they are shown. Empty when the change has none.'],
    ['name' => 'screenshots[]', 'type' => 'object', 'description' => 'One screenshot of the change.'],
    ['name' => 'screenshots[].position', 'type' => 'integer', 'description' => 'Where the screenshot stands among the others, counting from 1.'],
    ['name' => 'screenshots[].url', 'type' => 'string', 'description' => 'The absolute address of the screenshot, a WebP image.'],
    ['name' => 'screenshots[].description', 'type' => 'string', 'description' => 'What the screenshot shows, in your language.'],
    ['name' => 'screenshots[].width', 'type' => 'integer', 'description' => 'The width of the screenshot, in pixels.'],
    ['name' => 'screenshots[].height', 'type' => 'integer', 'description' => 'The height of the screenshot, in pixels.'],
    ['name' => 'day', 'type' => 'date', 'description' => 'The day the change was published, as YYYY-MM-DD, in UTC.'],
    ['name' => 'is_seen', 'type' => 'boolean', 'description' => 'Whether you have seen it. A change published before you joined counts as seen.'],
    ['name' => 'my_vote', 'type' => 'string', 'description' => 'up or down when you voted on the change, or null.'],
    ['name' => 'comment_count', 'type' => 'integer', 'description' => 'How many comments everybody can read under the change. Private comments and comments hidden by an administrator are not counted.'],
    ['name' => 'published_at', 'type' => 'timestamp', 'description' => 'When the change was published, as a count of seconds since 1970.'],
];

$commentAttributes = [
    ['name' => 'author_name', 'type' => 'string', 'description' => 'The name of the person who wrote it, or Anonymous, in your language, when they chose not to show it. An anonymous comment shows Anonymous to everybody, its author included.'],
    ['name' => 'is_mine', 'type' => 'boolean', 'description' => 'Whether you wrote it.'],
    ['name' => 'is_private', 'type' => 'boolean', 'description' => 'Whether only the administrators of the instance read it. Only its author sees a private comment in this list.'],
    ['name' => 'is_anonymous', 'type' => 'boolean', 'description' => 'Whether it is shown without the name of its author. A private comment is never anonymous.'],
    ['name' => 'body', 'type' => 'string', 'description' => 'The comment, as plain text.'],
    ['name' => 'replies', 'type' => 'array', 'description' => 'The public answers of the administrators, oldest first, or an empty list.'],
];

$commentTimestamps = [
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the comment was written, as a count of seconds since 1970.'],
    ['name' => 'updated_at', 'type' => 'timestamp', 'description' => 'When the comment last changed, as a count of seconds since 1970.'],
];

$entryParam = ['name' => 'entry', 'type' => 'string', 'required' => true, 'description' => 'The identifier of the change, the 26 characters it reports as id. A change that does not exist or is not published yet answers 404.'];

return [
    'name' => 'What’s new',
    'description' => 'The changes made to the product, as the What’s new screen shows them. A change is the same for every account, so there is nothing to create, change or delete. You can mark the changes as seen, vote on one, and comment on it. Titles, areas and explanations come back in the language of the person the key belongs to.',
    'sections' => [
        [
            'id' => 'changelog-entries-index',
            'returnFields' => [
                ...ApiDocsFields::page('changelog_entry', 'change', 'changes', $entryAttributes, withLinks: false),
                ['name' => 'data[].links', 'type' => 'object', 'description' => 'Where to find more about this change.'],
                ['name' => 'data[].links.comments', 'type' => 'string', 'description' => 'The absolute address the comments of the change are read from.'],
                ['name' => 'data[].links.web', 'type' => 'string', 'description' => 'The What’s new screen in the application, which opens for somebody signed in rather than for a key. It is the link to give a person.'],
                ['name' => 'meta.period', 'type' => 'integer', 'description' => 'How many days back the list reaches: 30 or 90.'],
                ['name' => 'meta.unseen_count', 'type' => 'integer', 'description' => 'How many published changes you have not seen yet, over the whole history rather than the period.'],
                ['name' => 'meta.days', 'type' => 'integer', 'description' => 'On how many different days the changes of the period were published.'],
                ['name' => 'meta.features', 'type' => 'integer', 'description' => 'How many changes of the period are features.'],
                ['name' => 'meta.improvements', 'type' => 'integer', 'description' => 'How many changes of the period are improvements.'],
                ['name' => 'meta.fixes', 'type' => 'integer', 'description' => 'How many changes of the period are fixes.'],
            ],
            'title' => 'List what’s new',
            'label' => 'List changes',
            'changelog' => true,
            'method' => 'GET',
            'path' => '/changelog-entries',
            'description' => 'List the changes published over the last 30 or 90 days, newest day first.',
            'body' => ['Within a day, features and improvements come before fixes, each newest first. A change scheduled for later is left out until it is published. Reading the list does not mark anything as seen.'],
            'rateLimit' => $rateLimit,
            'queryParams' => [
                ['name' => 'period', 'type' => 'integer', 'required' => false, 'description' => 'How many days back to reach: 30 or 90. Any other value reads as 30.', 'default' => '30', 'example' => 90],
                ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Which page to answer with.', 'default' => '1', 'example' => 2],
                ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'How many records one page holds, between 1 and 100. A number outside the range is brought back into it rather than refused.', 'default' => '25', 'example' => 10],
            ],
            'returns' => 'A page of change objects, how many there are in all, and in meta what the period holds.',
            'response' => [
                'data' => [$conferenceRoom, $faxMachine],
                'meta' => [
                    'page' => 1,
                    'per_page' => 25,
                    'total' => 2,
                    'period' => 30,
                    'unseen_count' => 1,
                    'days' => 1,
                    'features' => 1,
                    'improvements' => 0,
                    'fixes' => 1,
                ],
            ],
        ],
        [
            'id' => 'changelog-entries-seen',
            'title' => 'Mark every change as seen',
            'label' => 'Mark as seen',
            'changelog' => true,
            'method' => 'PUT',
            'path' => '/changelog-entries/seen',
            'description' => 'Mark every published change as seen by you, so unseen_count comes back to zero.',
            'body' => ['Changes you have already seen stay as they are, so calling it twice is harmless. A change published later is unseen again until you call it once more.'],
            'rateLimit' => $rateLimit,
            'returns' => 'Nothing.',
            'responseStatus' => 204,
            'response' => [],
        ],
        [
            'id' => 'changelog-entries-vote-update',
            'returnFields' => ApiDocsFields::nested('changelog_vote', 'vote', [
                ['name' => 'direction', 'type' => 'string', 'description' => 'up or down.'],
                ['name' => 'updated_at', 'type' => 'timestamp', 'description' => 'When the vote was last cast, as a count of seconds since 1970.'],
            ]),
            'title' => 'Vote on a change',
            'label' => 'Vote',
            'changelog' => true,
            'method' => 'PUT',
            'path' => '/changelog-entries/{entry}/vote',
            'examplePath' => "/changelog-entries/{$conferenceRoomId}/vote",
            'description' => 'Say whether you like a change.',
            'body' => [
                'You hold one vote per change, so voting the other way replaces the first vote. The id of the vote is the identifier of the change. A direction other than up or down answers 422 on direction.',
            ],
            'rateLimit' => $rateLimit,
            'pathParams' => [$entryParam],
            'bodyParams' => [
                ['name' => 'direction', 'type' => 'string', 'required' => true, 'description' => 'up or down.', 'example' => 'up'],
            ],
            'returns' => 'The vote object. A change that does not exist answers 404.',
            'response' => [
                'data' => [
                    'type' => 'changelog_vote',
                    'id' => $conferenceRoomId,
                    'attributes' => [
                        'direction' => 'up',
                        'updated_at' => 1794823200,
                    ],
                ],
            ],
        ],
        [
            'id' => 'changelog-entries-vote-destroy',
            'title' => 'Take back a vote',
            'changelog' => true,
            'method' => 'DELETE',
            'path' => '/changelog-entries/{entry}/vote',
            'examplePath' => "/changelog-entries/{$conferenceRoomId}/vote",
            'description' => 'Take back your vote on a change.',
            'body' => ['Taking back a vote you never cast is harmless.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$entryParam],
            'returns' => 'Nothing. A change that does not exist answers 404.',
            'responseStatus' => 204,
            'response' => [],
        ],
        [
            'id' => 'changelog-entries-comments-index',
            'returnFields' => [
                ['name' => 'data', 'type' => 'array', 'description' => 'Every comment you can read under the change, whole rather than a page at a time.'],
                ['name' => 'data[]', 'type' => 'object', 'description' => 'One comment.'],
                ['name' => 'data[].type', 'type' => 'string', 'description' => 'Always changelog_comment. It names the kind of record, so a caller holding several kinds can tell them apart without looking at where they came from.'],
                ['name' => 'data[].id', 'type' => 'string', 'description' => 'The identifier of this comment.'],
                ['name' => 'data[].attributes', 'type' => 'object', 'description' => 'Everything the comment holds.'],
                ...array_map(fn (array $attribute): array => [...$attribute, 'name' => "data[].attributes.{$attribute['name']}"], [...$commentAttributes, ...$commentTimestamps]),
                ['name' => 'data[].attributes.replies[]', 'type' => 'object', 'description' => 'One answer.'],
                ['name' => 'data[].attributes.replies[].author_name', 'type' => 'string', 'description' => 'The name the administrator answered under.'],
                ['name' => 'data[].attributes.replies[].body', 'type' => 'string', 'description' => 'The answer, as plain text.'],
                ['name' => 'data[].attributes.replies[].created_at', 'type' => 'timestamp', 'description' => 'When the answer was written, as a count of seconds since 1970.'],
            ],
            'title' => 'List the comments of a change',
            'label' => 'List comments',
            'changelog' => true,
            'method' => 'GET',
            'path' => '/changelog-entries/{entry}/comments',
            'examplePath' => "/changelog-entries/{$conferenceRoomId}/comments",
            'description' => 'List the comments you can read under a change, oldest first.',
            'body' => ['You read the public comments, except those an administrator hid, and every comment of your own, private or hidden. The list is whole rather than paginated.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$entryParam],
            'returns' => 'Every comment object you can read, or an empty list. A change that does not exist answers 404.',
            'response' => ['data' => [$comment, $anonymousComment]],
        ],
        [
            'id' => 'changelog-entries-comments-create',
            'returnFields' => ApiDocsFields::nested('changelog_comment', 'comment', [
                ...array_map(
                    fn (array $attribute): array => $attribute['name'] === 'replies'
                        ? [...$attribute, 'description' => 'The public answers of the administrators, which is an empty list for a new comment.']
                        : $attribute,
                    $commentAttributes,
                ),
                ...$commentTimestamps,
            ]),
            'title' => 'Comment on a change',
            'label' => 'Comment',
            'changelog' => true,
            'method' => 'POST',
            'path' => '/changelog-entries/{entry}/comments',
            'examplePath' => "/changelog-entries/{$conferenceRoomId}/comments",
            'description' => 'Write a comment under a change, for everybody or for the administrators only.',
            'body' => [
                'Markup is stripped from the comment. Every comment waits for an administrator to review it, and a public one is shown to everybody in the meantime. An administrator may hide a public comment, after which only its author reads it.',
                'A comment that is missing or longer than 2,000 characters answers 422 on body.',
            ],
            'rateLimit' => 'Ten requests per minute, per user, shared by every key and device.',
            'pathParams' => [$entryParam],
            'bodyParams' => [
                ['name' => 'body', 'type' => 'string', 'required' => true, 'description' => 'The comment, up to 2,000 characters.', 'example' => 'Can we book it for the Dundies?'],
                ['name' => 'is_private', 'type' => 'boolean', 'required' => false, 'description' => 'Whether only the administrators of the instance read it. False when left out.'],
                ['name' => 'is_anonymous', 'type' => 'boolean', 'required' => false, 'description' => 'Whether to show it without your name. False when left out, and ignored on a private comment.', 'example' => true],
            ],
            'returns' => 'The new comment object. A change that does not exist answers 404.',
            'responseStatus' => 201,
            'response' => ['data' => [...$anonymousComment, 'attributes' => [...$anonymousComment['attributes'], 'is_mine' => true]]],
        ],
    ],
];
