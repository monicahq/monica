<?php

declare(strict_types=1);

use App\Helpers\AvailableLocales;
use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();

$locales = AvailableLocales::codes();
$languages = count($locales) === 1
    ? $locales[0]
    : implode(', ', array_slice($locales, 0, -1)).' or '.$locales[count($locales) - 1];

$user = [
    'type' => 'user',
    'id' => '1',
    'attributes' => [
        'first_name' => 'Michael',
        'last_name' => 'Scott',
        'email' => 'michael.scott@dundermifflin.com',
        'email_verified' => true,
        'locale' => 'en',
        'timezone' => 'America/New_York',
        'time_format' => '24',
        'date_format' => null,
        'name_order' => 'given_first',
        'number_format' => null,
        'contact_list_grouping' => 'favourites',
        'contact_list_sort' => 'last-spoke',
        'contact_list_direction' => 'desc',
        'welcomed_at' => 1756252860,
        'created_at' => 1756252800,
        'updated_at' => 1756339200,
    ],
    'links' => [
        'self' => $base.'/me',
    ],
];

$userAttributes = [
    ['name' => 'first_name', 'type' => 'string', 'description' => 'The given name, up to 255 characters. Which of the two names is shown first is name_order rather than this.'],
    ['name' => 'last_name', 'type' => 'string', 'description' => 'The family name, up to 255 characters.'],
    ['name' => 'email', 'type' => 'string', 'description' => 'The address the account signs in with, up to 255 characters, always in lower case. It is unique across the instance.'],
    ['name' => 'email_verified', 'type' => 'boolean', 'description' => 'Whether the address has been confirmed. Worked out from when it was confirmed rather than stored as a flag, so the moment itself is not something the API hands out.'],
    ['name' => 'locale', 'type' => 'string', 'description' => "The language the product speaks to this person: one of {$languages}. It never affects anything the person themselves wrote."],
    ['name' => 'timezone', 'type' => 'string', 'description' => 'The zone their times are shown in, as an identifier such as Europe/Paris, up to 64 characters. Every timestamp in this API is a count of seconds regardless, so this is about what a screen shows rather than about what you receive.'],
    ['name' => 'time_format', 'type' => 'string', 'description' => 'Either 24 or 12, saying which clock they read.'],
    ['name' => 'date_format', 'type' => 'string', 'description' => 'How a date is written for them: one of d/m/Y, m/d/Y, Y-m-d or j F Y, or null when they have not chosen and the language decides.'],
    ['name' => 'name_order', 'type' => 'string', 'description' => 'Either given_first or family_first. Which name comes first varies by culture, so it is a setting rather than an assumption.'],
    ['name' => 'number_format', 'type' => 'string', 'description' => 'How a number is grouped and pointed: one of comma_dot, dot_comma, space_comma or no_separator, or null when they have not chosen and the language decides.'],
    ['name' => 'contact_list_grouping', 'type' => 'string', 'description' => 'Which people the contact list was last showing them: one of all, favourites, archived or names-only, or null until they have opened the list.'],
    ['name' => 'contact_list_sort', 'type' => 'string', 'description' => 'What the contact list was last ordered by: one of name, nickname, last-spoke or added, or null until they have opened the list.'],
    ['name' => 'contact_list_direction', 'type' => 'string', 'description' => 'Which way round that order ran: asc or desc, or null until they have opened the list.'],
    ['name' => 'welcomed_at', 'type' => 'timestamp', 'description' => 'When the user dismissed the welcome screen, as a count of seconds, or null if they have not.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the account was opened, as a count of seconds since 1970 rather than a written date.'],
    ['name' => 'updated_at', 'type' => 'timestamp', 'description' => 'When the profile last changed, as a count of seconds, or null if it has not changed since it was opened.'],
];

return [
    'name' => 'Your profile',
    'description' => 'The person behind the account: their name, the language they read in and the zone their days are counted in.',
    'sections' => [
        [
            'id' => 'me-show',
            'returnFields' => ApiDocsFields::one('user', 'person the token acts as', $userAttributes),
            'title' => 'Get your profile',
            'label' => 'Get your profile',
            'method' => 'GET',
            'path' => '/me',
            'description' => 'Retrieve the user the token belongs to. A token acts as one person, so there is nobody else to ask for here and no identifier in the URL.',
            'rateLimit' => 'Sixty requests per minute, per token.',
            'returns' => 'A user object. Credentials never appear in it: not the password, not the second factor, not the recovery codes.',
            'response' => ['data' => $user],
        ],
        [
            'id' => 'me-update',
            'returnFields' => ApiDocsFields::one('user', 'person the token acts as', $userAttributes),
            'title' => 'Update your profile',
            'label' => 'Update your profile',
            'method' => 'PUT',
            'path' => '/me',
            'description' => 'Update the name and the address of the user the token belongs to.',
            'body' => [
                'Changing the address unconfirms it, because nobody has proved yet that the new one reaches you. Preferences such as the locale, the time zone and the clock are not changed here.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'bodyParams' => [
                [
                    'name' => 'first_name',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'The first name of the user. Maximum 255 characters.',
                    'example' => 'Michael',
                ],
                [
                    'name' => 'last_name',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'The last name of the user. Maximum 255 characters.',
                    'example' => 'Scott',
                ],
                [
                    'name' => 'email',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'The address the user signs in with. Lowercase, and held by nobody else.',
                    'example' => 'michael.scott@dundermifflin.com',
                ],
            ],
            'returns' => 'The updated user object.',
            'response' => ['data' => $user],
        ],
        [
            'id' => 'me-contact-list-view-update',
            'returnFields' => ApiDocsFields::one('user', 'person the token acts as', $userAttributes),
            'title' => 'Set your contact list view',
            'label' => 'Set your contact list view',
            'method' => 'PUT',
            'path' => '/me/contact-list-view',
            'description' => 'Set the view the contact list opens on for the user the token belongs to.',
            'body' => [
                'The three choices are one view, so all three are required. Sending a grouping alone would leave the order to whatever happened to be stored, which is a different thing to ask for.',
                'It applies to every vault the user reaches, and it changes what the interface opens on rather than what this API answers: reading a contact list here takes its own filter, sort and direction, and neither reads nor writes this.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'bodyParams' => [
                [
                    'name' => 'grouping',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'Which people the list shows: all, favourites, archived or names-only.',
                    'example' => 'favourites',
                ],
                [
                    'name' => 'sort',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'What the list is ordered by: name, nickname, last-spoke or added.',
                    'example' => 'last-spoke',
                ],
                [
                    'name' => 'direction',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'Which way round that order runs: asc or desc.',
                    'example' => 'desc',
                ],
            ],
            'returns' => 'The updated user object.',
            'response' => ['data' => $user],
        ],
        [
            'id' => 'me-welcome-update',
            'returnFields' => ApiDocsFields::one('user', 'person the token acts as', $userAttributes),
            'title' => 'Dismiss the welcome screen',
            'label' => 'Dismiss the welcome screen',
            'method' => 'PUT',
            'path' => '/me/welcome',
            'description' => 'Record that the user the token belongs to has read the welcome screen.',
            'body' => [
                'A new account is sent to a welcome screen the first time it reaches the web application. An application that shows its own welcome calls this so the web one does not greet the same person twice.',
                'Dismissing it twice keeps the first moment, because that is when it was read. The screen itself stays open to anybody who asks for it, so there is nothing to undo.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'returns' => 'The updated user object.',
            'response' => ['data' => $user],
        ],
        [
            'id' => 'me-timezone-update',
            'returnFields' => ApiDocsFields::one('user', 'person the token acts as', $userAttributes),
            'title' => 'Set your time zone',
            'label' => 'Set your time zone',
            'method' => 'PUT',
            'path' => '/me/timezone',
            'description' => 'Set the time zone the user the token belongs to reads dates in.',
            'body' => [
                'The zone has to be one of the 419 canonical IANA identifiers, which is the same set the interface offers. The deprecated aliases such as US/Pacific are refused, and so are the bare offsets under Etc. Anything else comes back 422 with the stored zone untouched.',
                'It changes rendering and nothing else. No stored value is rewritten, and no reminder already computed moves: a reminder rule freezes the zone it was created in.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'bodyParams' => [
                [
                    'name' => 'timezone',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'An IANA time zone identifier. Maximum 64 characters.',
                    'example' => 'Europe/Lisbon',
                ],
            ],
            'returns' => 'The updated user object.',
            'response' => ['data' => $user],
        ],
    ],
];
