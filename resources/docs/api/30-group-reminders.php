<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();

$vaultSlug = 'dunder-mifflin';
$groupId = '5d2a7c91-3e48-4b06-9f1d-a83c60e2b4f7';
$reminderId = '7c1e4a82-5b39-4d07-8f62-c3a9e0d51b84';
$occurrenceId = 'b2f8d051-6e93-4a17-9c4b-58e1a7f03c26';

$group = [
    'id' => $groupId,
    'name' => 'Party planning committee',
];

$reminder = [
    'type' => 'reminder',
    'id' => $reminderId,
    'attributes' => [
        'name' => 'Book the conference room for the birthday party',
        'source' => 'manual',
        'starts_on' => '2026-11-20',
        'field' => null,
        'contact' => null,
        'group' => $group,
        'cadence' => 'monthly',
        'interval' => 1,
        'time' => '09:00',
        'timezone' => 'America/New_York',
        'is_active' => true,
        'inactive_reason' => null,
        'next_date' => '2026-11-20',
        'created_at' => 1791547200,
    ],
    'links' => [
        'self' => $base.'/vaults/'.$vaultSlug.'/groups/'.$groupId.'/reminders/'.$reminderId,
    ],
];

$occurrence = [
    'type' => 'reminder_occurrence',
    'id' => $occurrenceId,
    'attributes' => [
        'reminder' => $reminderId,
        'name' => 'Book the conference room for the birthday party',
        'group' => $group,
        'date' => '2026-10-20',
        'scheduled_for' => 1792501200,
        'category' => null,
        'answer' => 'remembered',
        'answered_at' => 1792504800,
        'cancelled_reason' => null,
    ],
    'links' => [
        'reminder' => $base.'/vaults/'.$vaultSlug.'/groups/'.$groupId.'/reminders/'.$reminderId,
    ],
];

$flagNote = 'Groups are not yet available to every account. Until they are available to yours, every endpoint here answers 404.';

$groupIdentifier = [
    ['name' => 'vault', 'type' => 'string', 'required' => true, 'description' => 'The vault the group lives in, named either by the readable name it reports as slug or by its id.'],
    ['name' => 'group', 'type' => 'uuid', 'required' => true, 'description' => 'The id of the group. One addressed through the wrong vault, deleted, or belonging to another account, answers 404.'],
];

$reminderIdentifier = [
    ...$groupIdentifier,
    ['name' => 'reminder', 'type' => 'uuid', 'required' => true, 'description' => 'The id of the reminder. One about another group answers 404, and so does a deleted one.'],
];

$pageParams = [
    ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Which page to answer with.', 'default' => '1', 'example' => 2],
    ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'How many records one page holds, between 1 and 100. A number outside the range is brought back into it rather than refused.', 'default' => '25', 'example' => 10],
];

$groupAttributes = [
    ['name' => 'group', 'type' => 'object', 'description' => 'The group the reminder is about.'],
    ['name' => 'group.id', 'type' => 'uuid', 'description' => 'The id of the group.'],
    ['name' => 'group.name', 'type' => 'string', 'description' => 'What the group is called.'],
];

$reminderAttributes = [
    ['name' => 'name', 'type' => 'string', 'description' => 'What the reminder says when it comes due.'],
    ['name' => 'source', 'type' => 'string', 'description' => 'Where its date comes from. Always manual for a group, since a group has no date of its own for a reminder to read.'],
    ['name' => 'starts_on', 'type' => 'string', 'description' => 'The first date, written as a year, a month and a day.'],
    ['name' => 'field', 'type' => 'object', 'description' => 'The date on a contact a reminder reads. Always null for a group.'],
    ['name' => 'contact', 'type' => 'object', 'description' => 'The contact a reminder is about. Always null for a group.'],
    ...$groupAttributes,
    ['name' => 'cadence', 'type' => 'string', 'description' => 'How often it comes back: once, weekly, monthly or yearly.'],
    ['name' => 'interval', 'type' => 'integer', 'description' => 'How many of those between two dates. Every 3 months is monthly with an interval of 3.'],
    ['name' => 'time', 'type' => 'string', 'description' => 'The local hour it is told at, on the day itself.'],
    ['name' => 'timezone', 'type' => 'string', 'description' => 'The time zone that hour is read in, fixed when the reminder was made.'],
    ['name' => 'is_active', 'type' => 'boolean', 'description' => 'Whether it still produces dates.'],
    ['name' => 'inactive_reason', 'type' => 'string', 'description' => 'Why it stopped, such as record_deleted. Null while it is active.'],
    ['name' => 'next_date', 'type' => 'string', 'description' => 'The next date it comes due, or null when nothing is ahead of it.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the reminder was made, as a count of seconds since 1970.'],
];

$occurrenceAttributes = [
    ['name' => 'reminder', 'type' => 'uuid', 'description' => 'The reminder this date belongs to.'],
    ['name' => 'name', 'type' => 'string', 'description' => 'What the reminder says.'],
    ...$groupAttributes,
    ['name' => 'date', 'type' => 'string', 'description' => 'The day it falls on, written as a year, a month and a day, in the reminder\'s time zone.'],
    ['name' => 'scheduled_for', 'type' => 'timestamp', 'description' => 'The instant it is told at, as a count of seconds since 1970.'],
    ['name' => 'category', 'type' => 'string', 'description' => 'Where it stands while it waits for an answer, read against today in your time zone: upcoming before its day, today on its day, overdue from the day after. Null once it is answered or cancelled.'],
    ['name' => 'answer', 'type' => 'string', 'description' => 'What you said about this date: remembered or forgotten. Null while nobody answered. No answer is ever filled in for you, and a notification sent or read never counts as one.'],
    ['name' => 'answered_at', 'type' => 'timestamp', 'description' => 'When the answer was given, as a count of seconds, or null.'],
    ['name' => 'cancelled_reason', 'type' => 'string', 'description' => 'Why the date will not be asked about any more, such as rule_deleted or rule_changed. Null otherwise. A cancelled date is never counted as forgotten.'],
];

return [
    'name' => 'Group reminders',
    'description' => 'The reminders about a group as a whole, the dates they produce, and what you answered for each date. A reminder about a group belongs to the group alone and never appears on the page of anybody in it.',
    'sections' => [
        [
            'id' => 'group-reminders-index',
            'flag' => 'groups',
            'returnFields' => ApiDocsFields::page('reminder', 'reminder', 'reminders', $reminderAttributes),
            'title' => 'List the reminders of a group',
            'label' => 'List reminders',
            'method' => 'GET',
            'path' => '/vaults/{vault}/groups/{group}/reminders',
            'examplePath' => '/vaults/'.$vaultSlug.'/groups/'.$groupId.'/reminders',
            'description' => 'List every reminder about one group.',
            'body' => [
                $flagNote,
                'A reminder is told on its day, once on each channel you switched on, and never again. Nothing is sent ahead of the day.',
                'Deleted reminders are left out. The answers given to their dates stay, and are read from the dates endpoint.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $groupIdentifier,
            'queryParams' => $pageParams,
            'returns' => 'A page of reminder objects, and how many there are in all.',
            'response' => [
                'data' => [$reminder],
                'meta' => ['page' => 1, 'per_page' => 25, 'total' => 1],
            ],
        ],
        [
            'id' => 'group-reminders-show',
            'flag' => 'groups',
            'returnFields' => ApiDocsFields::one('reminder', 'reminder', $reminderAttributes),
            'title' => 'Get a reminder of a group',
            'label' => 'Get a reminder',
            'method' => 'GET',
            'path' => '/vaults/{vault}/groups/{group}/reminders/{reminder}',
            'examplePath' => '/vaults/'.$vaultSlug.'/groups/'.$groupId.'/reminders/'.$reminderId,
            'description' => 'Retrieve one reminder about a group.',
            'body' => [
                $flagNote,
                'The dates it produced, and what was answered for each, are read from the dates endpoint.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $reminderIdentifier,
            'returns' => 'A reminder object. A reminder of another account answers 404.',
            'response' => ['data' => $reminder],
        ],
        [
            'id' => 'group-reminders-create',
            'flag' => 'groups',
            'returnFields' => ApiDocsFields::one('reminder', 'reminder', $reminderAttributes),
            'title' => 'Add a reminder to a group',
            'label' => 'Add a reminder',
            'method' => 'POST',
            'path' => '/vaults/{vault}/groups/{group}/reminders',
            'examplePath' => '/vaults/'.$vaultSlug.'/groups/'.$groupId.'/reminders',
            'description' => 'Write a reminder about a group as a whole, with a date of its own. The change is written to the account log.',
            'body' => [
                $flagNote,
                'A starts_on before today answers 422 on starts_on, saying a reminder cannot start in the past. One that happens once, dated today, whose hour has already passed, answers 422 on time, saying the time for this reminder has already passed today.',
                'A name left empty once reduced to plain text answers 422 on name, asking to say what the reminder is about. A time zone the application does not know answers 422 on timezone.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $groupIdentifier,
            'bodyParams' => [
                ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => 'What the reminder says. Up to 255 characters, reduced to plain text.', 'example' => 'Book the conference room for the birthday party'],
                ['name' => 'starts_on', 'type' => 'string', 'required' => true, 'description' => 'The first date, written as a year, a month and a day.', 'example' => '2026-11-20'],
                ['name' => 'cadence', 'type' => 'string', 'required' => true, 'description' => 'One of once, weekly, monthly or yearly.', 'example' => 'monthly'],
                ['name' => 'interval', 'type' => 'integer', 'required' => false, 'description' => 'How many of those between two dates, from 1 to 99. Ignored for once.', 'example' => '1'],
                ['name' => 'time', 'type' => 'string', 'required' => true, 'description' => 'The local hour to be told at, as hours and minutes.', 'example' => '09:00'],
                ['name' => 'timezone', 'type' => 'string', 'required' => false, 'description' => 'The time zone that hour is read in. Your own when left out.', 'example' => 'America/New_York'],
            ],
            'returns' => 'The reminder object that was added.',
            'responseStatus' => 201,
            'response' => ['data' => $reminder],
        ],
        [
            'id' => 'group-reminders-update',
            'flag' => 'groups',
            'returnFields' => ApiDocsFields::one('reminder', 'reminder', $reminderAttributes),
            'title' => 'Change a reminder of a group',
            'label' => 'Change a reminder',
            'method' => 'PUT',
            'path' => '/vaults/{vault}/groups/{group}/reminders/{reminder}',
            'examplePath' => '/vaults/'.$vaultSlug.'/groups/'.$groupId.'/reminders/'.$reminderId,
            'description' => 'Change what a reminder about a group says and when it comes back. The change is written to the account log.',
            'body' => [
                $flagNote,
                'Changing the schedule replaces the dates after today that nobody answered. Today\'s date and the overdue ones stay as they are, and no answer already given is ever changed.',
                'A new starts_on before today answers 422 on starts_on, saying a reminder cannot start in the past. A reminder sent once whose date has already passed answers 422 on starts_on, saying the date has already passed, so a reminder sent once would never arrive. A time zone the application does not know answers 422 on timezone. An interval above the most a reminder may skip answers 422 on interval.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $reminderIdentifier,
            'bodyParams' => [
                ['name' => 'name', 'type' => 'string', 'required' => false, 'description' => 'What the reminder says. Up to 255 characters. It cannot be null.', 'example' => 'Book the conference room for the birthday party'],
                ['name' => 'starts_on', 'type' => 'string', 'required' => false, 'description' => 'A new first date.', 'example' => '2026-11-20'],
                ['name' => 'cadence', 'type' => 'string', 'required' => false, 'description' => 'One of once, weekly, monthly or yearly. It cannot be null.', 'example' => 'monthly'],
                ['name' => 'interval', 'type' => 'integer', 'required' => false, 'description' => 'How many of those between two dates, from 1 to 99.', 'example' => '1'],
                ['name' => 'time', 'type' => 'string', 'required' => false, 'description' => 'The local hour to be told at. It cannot be null.', 'example' => '09:00'],
                ['name' => 'timezone', 'type' => 'string', 'required' => false, 'description' => 'A new time zone.', 'example' => 'America/New_York'],
            ],
            'returns' => 'The reminder object as it stands now.',
            'response' => ['data' => $reminder],
        ],
        [
            'id' => 'group-reminders-destroy',
            'flag' => 'groups',
            'title' => 'Delete a reminder of a group',
            'label' => 'Delete a reminder',
            'method' => 'DELETE',
            'path' => '/vaults/{vault}/groups/{group}/reminders/{reminder}',
            'examplePath' => '/vaults/'.$vaultSlug.'/groups/'.$groupId.'/reminders/'.$reminderId,
            'description' => 'Stop a reminder about a group for good. There is no pause: deleting is the only way to stop one.',
            'body' => [
                $flagNote,
                'Nothing further is delivered, and the dates still waiting for an answer are dropped. The answers already given stay, and are still read from the dates endpoint.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $reminderIdentifier,
            'returns' => 'Nothing at all.',
            'responseStatus' => 204,
        ],
        [
            'id' => 'group-reminder-occurrences-index',
            'flag' => 'groups',
            'returnFields' => [
                ...ApiDocsFields::page('reminder_occurrence', 'date', 'dates', $occurrenceAttributes, withLinks: false),
                ['name' => 'data[].links', 'type' => 'object', 'description' => 'Where to find what this date belongs to.'],
                ['name' => 'data[].links.reminder', 'type' => 'string', 'description' => 'The absolute address of the reminder, or null once the reminder was deleted.'],
            ],
            'title' => 'List the reminder dates of a group',
            'label' => 'List reminder dates',
            'method' => 'GET',
            'path' => '/vaults/{vault}/groups/{group}/reminder-occurrences',
            'examplePath' => '/vaults/'.$vaultSlug.'/groups/'.$groupId.'/reminder-occurrences',
            'description' => 'List the dates the reminders about a group produced, with what was answered for each.',
            'body' => [
                $flagNote,
                'Without a category, every date is listed, newest first, so any date can be looked up: remembered, forgotten, or never answered.',
                'upcoming lists the next date of each active reminder. A date too far off to have been generated yet comes back with a null id, and cannot be answered, which nothing upcoming can be anyway.',
                'history lists the answered dates, the latest answer first, including those of reminders deleted since. A category it does not know answers 422.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $groupIdentifier,
            'queryParams' => [
                ['name' => 'category', 'type' => 'string', 'required' => false, 'description' => 'One of overdue, today, upcoming or history.', 'example' => 'history'],
                ...$pageParams,
            ],
            'returns' => 'A page of reminder date objects, and how many there are in all.',
            'response' => [
                'data' => [$occurrence],
                'meta' => ['page' => 1, 'per_page' => 25, 'total' => 1],
            ],
        ],
        [
            'id' => 'group-reminder-occurrences-answer-update',
            'flag' => 'groups',
            'returnFields' => [
                ...ApiDocsFields::one('reminder_occurrence', 'date', $occurrenceAttributes, withLinks: false),
                ['name' => 'data.links', 'type' => 'object', 'description' => 'Where to find what this date belongs to.'],
                ['name' => 'data.links.reminder', 'type' => 'string', 'description' => 'The absolute address of the reminder.'],
            ],
            'title' => 'Answer a reminder date of a group',
            'label' => 'Answer a reminder date',
            'method' => 'PUT',
            'path' => '/vaults/{vault}/groups/{group}/reminder-occurrences/{occurrence}/answer',
            'examplePath' => '/vaults/'.$vaultSlug.'/groups/'.$groupId.'/reminder-occurrences/'.$occurrenceId.'/answer',
            'description' => 'Say whether you remembered a date or forgot it. The answer is final, and the change is written to the account log.',
            'body' => [
                $flagNote,
                'remembered is accepted from the date\'s own day, and earlier answers 422 on answer, saying the reminder is not due yet. forgotten is accepted from the day after, and earlier answers 422 on answer, saying you can say you forgot from the day after. Both are read against today in your time zone.',
                'An answer cannot be changed or taken back. A second answer for the same date answers 422 on answer, saying the reminder has already been answered, whatever it says. A cancelled date answers 422 on answer, saying it was cancelled and cannot be answered.',
                'Answering drops the notifications for that date not sent yet, and touches no other date.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => [
                ...$groupIdentifier,
                ['name' => 'occurrence', 'type' => 'uuid', 'required' => true, 'description' => 'The id of the date. A date of another group answers 404.'],
            ],
            'bodyParams' => [
                ['name' => 'answer', 'type' => 'string', 'required' => true, 'description' => 'remembered or forgotten.', 'example' => 'remembered'],
            ],
            'returns' => 'The reminder date object, with its answer.',
            'response' => ['data' => $occurrence],
        ],
    ],
];
