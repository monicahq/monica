<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();

$vaultSlug = 'dunder-mifflin';
$contactSlug = 'pam-beesly';
$reminderId = '4e7b2c90-3d15-4a86-9f21-8b0c6e5d7a13';
$occurrenceId = 'a9d3f6e1-2b47-4c08-8e5a-17f4c0b9d262';

$reminder = [
    'type' => 'reminder',
    'id' => $reminderId,
    'attributes' => [
        'name' => 'Ask how the art school application went',
        'source' => 'manual',
        'starts_on' => '2026-10-14',
        'field' => null,
        'cadence' => 'monthly',
        'interval' => 1,
        'time' => '09:00',
        'timezone' => 'America/New_York',
        'is_active' => true,
        'inactive_reason' => null,
        'next_date' => '2026-10-14',
        'created_at' => 1790380800,
    ],
    'links' => [
        'self' => $base.'/vaults/'.$vaultSlug.'/contacts/'.$contactSlug.'/reminders/'.$reminderId,
    ],
];

$occurrence = [
    'type' => 'reminder_occurrence',
    'id' => $occurrenceId,
    'attributes' => [
        'reminder' => $reminderId,
        'name' => 'Ask how the art school application went',
        'date' => '2026-09-14',
        'scheduled_for' => 1789390800,
        'category' => null,
        'answer' => 'remembered',
        'answered_at' => 1789394400,
        'cancelled_reason' => null,
    ],
    'links' => [
        'reminder' => $base.'/vaults/'.$vaultSlug.'/contacts/'.$contactSlug.'/reminders/'.$reminderId,
    ],
];

$identifier = [
    ['name' => 'vault', 'type' => 'string', 'required' => true, 'description' => 'The vault the contact lives in, named either by the readable name it reports as slug or by the identifier it reports as id.'],
    ['name' => 'contact', 'type' => 'string', 'required' => true, 'description' => 'The contact the reminders are about, named either by its slug or by its id. A contact addressed through the wrong vault answers 404.'],
];

$reminderIdentifier = [
    ...$identifier,
    ['name' => 'reminder', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of the reminder. A reminder about somebody else answers 404, and so does a deleted one.'],
];

$pageParams = [
    ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Which page to answer with.', 'default' => '1'],
    ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'How many records one page holds, between 1 and 100. A number outside the range is brought back into it rather than refused.', 'default' => '25'],
];

$reminderAttributes = [
    ['name' => 'name', 'type' => 'string', 'description' => 'What the reminder says when it comes due.'],
    ['name' => 'source', 'type' => 'string', 'description' => 'Where its date comes from: manual when somebody wrote the reminder with a date of its own, field_value when it reads a date on the contact, such as a birthday.'],
    ['name' => 'starts_on', 'type' => 'string', 'description' => 'The first date of a manual reminder, written as a year, a month and a day. Null for a reminder that reads a date on the contact.'],
    ['name' => 'field', 'type' => 'object', 'description' => 'The date on the contact the reminder reads, as an object holding key, how the field is named in an import or an export, and label, what it is called on the contact. Null for a manual reminder.'],
    ['name' => 'cadence', 'type' => 'string', 'description' => 'How often it comes back: once, weekly, monthly or yearly.'],
    ['name' => 'interval', 'type' => 'integer', 'description' => 'How many of those between two dates. Every 3 months is monthly with an interval of 3.'],
    ['name' => 'time', 'type' => 'string', 'description' => 'The local hour it is told at, on the day itself.'],
    ['name' => 'timezone', 'type' => 'string', 'description' => 'The time zone that hour is read in, fixed when the reminder was made.'],
    ['name' => 'is_active', 'type' => 'boolean', 'description' => 'Whether it still produces dates. A reminder about an archived contact, or one whose date was cleared, stops and says why.'],
    ['name' => 'inactive_reason', 'type' => 'string', 'description' => 'Why it stopped: value_cleared, record_archived, record_deleted or field_removed. Null while it is active.'],
    ['name' => 'next_date', 'type' => 'string', 'description' => 'The next date it comes due, or null when nothing is ahead of it.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the reminder was made, as a count of seconds since 1970.'],
];

$occurrenceAttributes = [
    ['name' => 'reminder', 'type' => 'uuid', 'description' => 'The reminder this date belongs to.'],
    ['name' => 'name', 'type' => 'string', 'description' => 'What the reminder says.'],
    ['name' => 'date', 'type' => 'string', 'description' => 'The day it falls on, written as a year, a month and a day, in the reminder\'s time zone.'],
    ['name' => 'scheduled_for', 'type' => 'timestamp', 'description' => 'The instant it is told at, as a count of seconds since 1970.'],
    ['name' => 'category', 'type' => 'string', 'description' => 'Where it stands while it waits for an answer, read against today in your time zone: upcoming before its day, today on its day, overdue from the day after. Null once it is answered or cancelled.'],
    ['name' => 'answer', 'type' => 'string', 'description' => 'What you said about this date: remembered or forgotten. Null while nobody answered. No answer is ever filled in for you, and a notification sent or read never counts as one.'],
    ['name' => 'answered_at', 'type' => 'timestamp', 'description' => 'When the answer was given, as a count of seconds, or null.'],
    ['name' => 'cancelled_reason', 'type' => 'string', 'description' => 'Why the date will not be asked about any more: rule_deleted, record_archived, record_deleted, field_removed, value_cleared, source_changed or rule_changed. Null otherwise. A cancelled date is never counted as forgotten.'],
];

return [
    'name' => 'Reminders',
    'description' => 'The reminders about a contact, the dates they produce, and what you answered for each date.',
    'sections' => [
        [
            'id' => 'reminders-index',
            'returnFields' => ApiDocsFields::page('reminder', 'reminder', 'reminders', $reminderAttributes),
            'title' => 'List a contact\'s reminders',
            'label' => 'List reminders',
            'method' => 'GET',
            'path' => '/vaults/{vault}/contacts/{contact}/reminders',
            'examplePath' => '/vaults/'.$vaultSlug.'/contacts/'.$contactSlug.'/reminders',
            'description' => 'List every reminder about one contact, the ones written by hand and the ones reading a date on their page.',
            'body' => [
                'A reminder is told on its day, once on each channel you switched on, and never again. Nothing is sent ahead of the day.',
                'Deleted reminders are left out. The answers given to their dates stay, and are read from the dates endpoint.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => $identifier,
            'queryParams' => $pageParams,
            'returns' => 'A page of reminder objects, and how many there are in all.',
            'response' => [
                'data' => [$reminder],
                'meta' => ['page' => 1, 'per_page' => 25, 'total' => 1],
            ],
        ],
        [
            'id' => 'reminders-create',
            'returnFields' => ApiDocsFields::one('reminder', 'reminder', $reminderAttributes),
            'title' => 'Add a reminder',
            'label' => 'Add a reminder',
            'method' => 'POST',
            'path' => '/vaults/{vault}/contacts/{contact}/reminders',
            'examplePath' => '/vaults/'.$vaultSlug.'/contacts/'.$contactSlug.'/reminders',
            'description' => 'Write a reminder about a contact with a date of its own.',
            'body' => [
                'A reminder reading a date on the contact, such as a birthday, is asked for on the contact\'s page and cannot be made here.',
                'A reminder cannot start before today. One that happens once, dated today, whose hour has already passed, is refused with 422, since there would be nothing left to tell you about.',
                'A refusal names the field it is about: name, starts_on, interval, time or timezone.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => $identifier,
            'bodyParams' => [
                ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => 'What the reminder says. Up to 255 characters, reduced to plain text.', 'example' => 'Ask how the art school application went'],
                ['name' => 'starts_on', 'type' => 'string', 'required' => true, 'description' => 'The first date, written as a year, a month and a day.', 'example' => '2026-10-14'],
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
            'id' => 'reminders-show',
            'returnFields' => ApiDocsFields::one('reminder', 'reminder', $reminderAttributes),
            'title' => 'Get a reminder',
            'label' => 'Get a reminder',
            'method' => 'GET',
            'path' => '/vaults/{vault}/contacts/{contact}/reminders/{reminder}',
            'examplePath' => '/vaults/'.$vaultSlug.'/contacts/'.$contactSlug.'/reminders/'.$reminderId,
            'description' => 'Retrieve one reminder about a contact.',
            'body' => [
                'The dates it produced, and what was answered for each, are read from the dates endpoint.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => $reminderIdentifier,
            'returns' => 'A reminder object.',
            'response' => ['data' => $reminder],
        ],
        [
            'id' => 'reminders-update',
            'returnFields' => ApiDocsFields::one('reminder', 'reminder', $reminderAttributes),
            'title' => 'Change a reminder',
            'label' => 'Change a reminder',
            'method' => 'PUT',
            'path' => '/vaults/{vault}/contacts/{contact}/reminders/{reminder}',
            'examplePath' => '/vaults/'.$vaultSlug.'/contacts/'.$contactSlug.'/reminders/'.$reminderId,
            'description' => 'Change what a reminder says and when it comes back.',
            'body' => [
                'Changing the schedule replaces the dates after today that nobody answered. Today\'s date and the overdue ones stay as they are, and no answer already given is ever changed.',
                'Only a manual reminder has a first date of its own. Sending starts_on for one that reads a date on the contact answers 422.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => $reminderIdentifier,
            'bodyParams' => [
                ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => 'What the reminder says. Up to 255 characters.', 'example' => 'Ask how the art school application went'],
                ['name' => 'starts_on', 'type' => 'string', 'required' => false, 'description' => 'A new first date, for a manual reminder only.', 'example' => '2026-10-14'],
                ['name' => 'cadence', 'type' => 'string', 'required' => true, 'description' => 'One of once, weekly, monthly or yearly.', 'example' => 'monthly'],
                ['name' => 'interval', 'type' => 'integer', 'required' => false, 'description' => 'How many of those between two dates, from 1 to 99.', 'example' => '1'],
                ['name' => 'time', 'type' => 'string', 'required' => true, 'description' => 'The local hour to be told at.', 'example' => '09:00'],
                ['name' => 'timezone', 'type' => 'string', 'required' => false, 'description' => 'A new time zone. The one it has is kept when left out.', 'example' => 'America/New_York'],
            ],
            'returns' => 'The reminder object as it stands now.',
            'response' => ['data' => $reminder],
        ],
        [
            'id' => 'reminders-destroy',
            'title' => 'Delete a reminder',
            'label' => 'Delete a reminder',
            'method' => 'DELETE',
            'path' => '/vaults/{vault}/contacts/{contact}/reminders/{reminder}',
            'examplePath' => '/vaults/'.$vaultSlug.'/contacts/'.$contactSlug.'/reminders/'.$reminderId,
            'description' => 'Stop a reminder for good. There is no pause: deleting is the only way to stop one.',
            'body' => [
                'Nothing further is delivered, and the dates still waiting for an answer are dropped. The answers already given stay, and are still read from the dates endpoint.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => $reminderIdentifier,
            'returns' => 'Nothing at all.',
            'responseStatus' => 204,
        ],
        [
            'id' => 'reminder-occurrences-index',
            'returnFields' => [
                ...ApiDocsFields::page('reminder_occurrence', 'date', 'dates', $occurrenceAttributes, withLinks: false),
                ['name' => 'data[].links', 'type' => 'object', 'description' => 'Where to find what this date belongs to.'],
                ['name' => 'data[].links.reminder', 'type' => 'string', 'description' => 'The absolute address of the reminder, or null once the reminder was deleted.'],
            ],
            'title' => 'List a contact\'s reminder dates',
            'label' => 'List reminder dates',
            'method' => 'GET',
            'path' => '/vaults/{vault}/contacts/{contact}/reminder-occurrences',
            'examplePath' => '/vaults/'.$vaultSlug.'/contacts/'.$contactSlug.'/reminder-occurrences?category=history',
            'description' => 'List the dates the reminders about a contact produced, with what was answered for each.',
            'body' => [
                'Without a category, every date is listed, newest first, so any date can be looked up: remembered, forgotten, or never answered.',
                'upcoming lists the next date of each active reminder. A date too far off to have been generated yet comes back with a null id, and cannot be answered, which nothing upcoming can be anyway.',
                'history lists the answered dates, the latest answer first, including those of reminders deleted since.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => $identifier,
            'queryParams' => [
                ['name' => 'category', 'type' => 'string', 'required' => false, 'description' => 'One of overdue, today, upcoming or history.'],
                ...$pageParams,
            ],
            'returns' => 'A page of reminder date objects, and how many there are in all.',
            'response' => [
                'data' => [$occurrence],
                'meta' => ['page' => 1, 'per_page' => 25, 'total' => 1],
            ],
        ],
        [
            'id' => 'reminder-occurrences-answer',
            'returnFields' => [
                ...ApiDocsFields::one('reminder_occurrence', 'date', $occurrenceAttributes, withLinks: false),
                ['name' => 'data.links', 'type' => 'object', 'description' => 'Where to find what this date belongs to.'],
                ['name' => 'data.links.reminder', 'type' => 'string', 'description' => 'The absolute address of the reminder.'],
            ],
            'title' => 'Answer a reminder date',
            'label' => 'Answer a reminder date',
            'method' => 'PUT',
            'path' => '/vaults/{vault}/contacts/{contact}/reminder-occurrences/{occurrence}/answer',
            'examplePath' => '/vaults/'.$vaultSlug.'/contacts/'.$contactSlug.'/reminder-occurrences/'.$occurrenceId.'/answer',
            'description' => 'Say whether you remembered a date or forgot it. The answer is final.',
            'body' => [
                'remembered is accepted from the date\'s own day. forgotten is accepted from the day after. Both are read against today in your time zone, and anything earlier answers 422.',
                'An answer cannot be changed or taken back, and a second answer for the same date answers 422, whatever it says. A cancelled date answers 422 too.',
                'Answering drops the notifications for that date not sent yet, and touches no other date: next year\'s birthday is still waiting.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => [
                ...$identifier,
                ['name' => 'occurrence', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of the date, which is the id it comes back with.'],
            ],
            'bodyParams' => [
                ['name' => 'answer', 'type' => 'string', 'required' => true, 'description' => 'remembered or forgotten.', 'example' => 'remembered'],
            ],
            'returns' => 'The reminder date object, with its answer.',
            'response' => ['data' => $occurrence],
        ],
    ],
];
