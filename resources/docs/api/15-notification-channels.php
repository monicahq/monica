<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();

$email = [
    'type' => 'notification_channel',
    'id' => 'email',
    'attributes' => [
        'channel' => 'email',
        'label' => 'Email',
        'is_enabled' => true,
        'destination' => null,
        'needs_destination' => false,
        'discovers_destination' => false,
        'requires_proof' => false,
        'is_proven' => true,
        'is_configured_on_the_instance' => true,
        'disabled_reason' => null,
    ],
    'links' => [
        'self' => $base.'/notification-channels/email',
    ],
];

$telegram = [
    'type' => 'notification_channel',
    'id' => 'telegram',
    'attributes' => [
        'channel' => 'telegram',
        'label' => 'Telegram',
        'is_enabled' => true,
        'destination' => '918273645',
        'needs_destination' => true,
        'discovers_destination' => true,
        'requires_proof' => false,
        'is_proven' => true,
        'is_configured_on_the_instance' => true,
        'disabled_reason' => null,
    ],
    'links' => [
        'self' => $base.'/notification-channels/telegram',
    ],
];

$sms = [
    'type' => 'notification_channel',
    'id' => 'sms',
    'attributes' => [
        'channel' => 'sms',
        'label' => 'Text message',
        'is_enabled' => false,
        'destination' => '+15705550172',
        'needs_destination' => true,
        'discovers_destination' => false,
        'requires_proof' => true,
        'is_proven' => false,
        'is_configured_on_the_instance' => true,
        'disabled_reason' => null,
    ],
    'links' => [
        'self' => $base.'/notification-channels/sms',
    ],
];

$provenSms = $sms;
$provenSms['attributes']['is_proven'] = true;

$channelAttributes = [
    ['name' => 'channel', 'type' => 'string', 'description' => 'Which channel this is: in_app, email, telegram or sms. It is also the address of the record, so this is what goes in the path.'],
    ['name' => 'label', 'type' => 'string', 'description' => 'What the channel is called, in the language you asked in.'],
    ['name' => 'is_enabled', 'type' => 'boolean', 'description' => 'Whether the account wants anything to go out this way. A channel can be switched on only once everything below is satisfied.'],
    ['name' => 'destination', 'type' => 'string', 'description' => 'Where messages go: the room, the conversation or the number, up to 255 characters, or null when the channel asks for none or none has been given.'],
    ['name' => 'needs_destination', 'type' => 'boolean', 'description' => 'Whether this channel has to be told where to send. Email does not, because the address is already on you.'],
    ['name' => 'discovers_destination', 'type' => 'boolean', 'description' => 'Whether the service hands us the destination rather than the account typing it. True for Telegram, where the account opens a conversation with the bot and the bot tells us which one it is. A destination sent for such a channel is refused.'],
    ['name' => 'requires_proof', 'type' => 'boolean', 'description' => 'Whether the destination has to be proved before the channel may be switched on. True only for channels that accept a wrong destination without complaint, which today means text messages.'],
    ['name' => 'is_proven', 'type' => 'boolean', 'description' => 'Whether it has been proved. Always true for a channel with nothing to prove.'],
    ['name' => 'is_configured_on_the_instance', 'type' => 'boolean', 'description' => 'Whether this particular server has been set up to send on the channel. False only for a channel whose provider account belongs to the instance, which today means Telegram and the text message. Switching on a channel that is false here answers 422.'],
    ['name' => 'disabled_reason', 'type' => 'string', 'description' => 'Why the product switched the channel off itself, up to 255 characters, or null when it did not. Set when a channel refuses everything sent to it several times in a row.'],
];

$preferenceAttributes = [
    ['name' => 'quiet_hours_are_enabled', 'type' => 'boolean', 'description' => 'Whether there are hours during which nothing may be delivered.'],
    ['name' => 'quiet_hours_start', 'type' => 'string', 'description' => 'The local time from which nothing may go out, as HH:MM, or null when quiet hours are off.'],
    ['name' => 'quiet_hours_end', 'type' => 'string', 'description' => 'The local time from which delivery may resume, as HH:MM, or null when quiet hours are off.'],
    ['name' => 'delivery_mode', 'type' => 'string', 'description' => 'Whether each notification goes out on its own, with immediate, or is folded into one summary a day, with digest.'],
    ['name' => 'digest_time', 'type' => 'string', 'description' => 'The local time the daily summary is sent, as HH:MM, or null when nothing is folded.'],
    ['name' => 'digest_weekdays', 'type' => 'array', 'description' => 'Which days the summary is sent, as numbers from 0 for Sunday to 6 for Saturday, or null for every day.'],
    ['name' => 'timezone', 'type' => 'string', 'description' => 'The zone quiet hours and the summary hour are read in, up to 64 characters.'],
];

$preference = [
    'type' => 'notification_preference',
    'id' => '17',
    'attributes' => [
        'quiet_hours_are_enabled' => true,
        'quiet_hours_start' => '22:00',
        'quiet_hours_end' => '08:00',
        'delivery_mode' => 'immediate',
        'digest_time' => null,
        'digest_weekdays' => null,
        'timezone' => 'America/New_York',
    ],
    'links' => [
        'self' => $base.'/notification-preferences',
    ],
];

$channelPathParam = [
    [
        'name' => 'channel',
        'type' => 'string',
        'required' => true,
        'description' => 'Which channel: in_app, email, telegram or sms. Anything else answers 404.',
    ],
];

return [
    'name' => 'Notification channels',
    'description' => 'Where a notification is delivered, proving a destination belongs to whoever named it.',
    'sections' => [
        [
            'id' => 'notification-channels-index',
            'returnFields' => ApiDocsFields::all('notification_channel', 'channel', 'channels', $channelAttributes),
            'title' => 'List notification channels',
            'label' => 'List channels',
            'method' => 'GET',
            'path' => '/notification-channels',
            'description' => 'List every way this account can be reached, and the state each one is in.',
            'body' => [
                'Every account has one record per channel from the moment it exists, so this list is never short and never has to be filled in first.',
                'No channel asks the account for a credential. In app and email need none, and the provider account behind Telegram and the text message belongs to the instance, so all the account supplies is where to send.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'returns' => 'Every channel of the account, in the order the product names them.',
            'response' => ['data' => [$email, $telegram, $sms]],
        ],
        [
            'id' => 'notification-channels-show',
            'returnFields' => ApiDocsFields::one('notification_channel', 'channel', $channelAttributes),
            'title' => 'Get a notification channel',
            'label' => 'Get a channel',
            'method' => 'GET',
            'path' => '/notification-channels/{channel}',
            'examplePath' => '/notification-channels/telegram',
            'description' => 'Retrieve one channel and what it still needs.',
            'body' => [
                'Read needs_destination and requires_proof against destination and is_proven to work out what is left to do before is_enabled can be set.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $channelPathParam,
            'returns' => 'One channel. A channel name the product does not know answers 404.',
            'response' => ['data' => $telegram],
        ],
        [
            'id' => 'notification-channels-update',
            'returnFields' => ApiDocsFields::one('notification_channel', 'channel', $channelAttributes),
            'title' => 'Change a notification channel',
            'label' => 'Change a channel',
            'method' => 'PUT',
            'path' => '/notification-channels/{channel}',
            'examplePath' => '/notification-channels/telegram',
            'description' => 'Set where a channel sends, what it signs in with, and whether it is on.',
            'body' => [
                'Switching a channel on is refused with 422 while anything it asks for is missing, and the message says which. A channel that has to be proved is refused until it has been.',
                'Changing destination throws away the proof, because the proof was about the old destination and says nothing about the new one.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $channelPathParam,
            'bodyParams' => [
                [
                    'name' => 'is_enabled',
                    'type' => 'boolean',
                    'required' => true,
                    'description' => 'Whether anything should go out this way. False is always allowed, whatever state the channel is in.',
                ],
                [
                    'name' => 'destination',
                    'type' => 'string',
                    'required' => false,
                    'description' => 'Where to send: the room, the conversation or the number, up to 255 characters. Left out, whatever is saved stays.',
                ],
            ],
            'returns' => 'The channel as it now stands.',
            'response' => ['data' => $telegram],
        ],
        [
            'id' => 'notification-channels-proof-store',
            'responseStatus' => 201,
            'returnFields' => ApiDocsFields::one('notification_channel', 'channel', $channelAttributes),
            'title' => 'Send a code to a destination',
            'label' => 'Send a code',
            'method' => 'POST',
            'path' => '/notification-channels/{channel}/proof',
            'examplePath' => '/notification-channels/sms/proof',
            'description' => 'Send a code to a destination to find out whether it is really yours.',
            'body' => [
                'Only a channel whose requires_proof is true accepts this. A text message provider takes any well formed number, so a send that appears to have worked proves nothing; a code that comes back proves somebody holding that number typed it.',
                'The code lasts a limited time and only so many may be asked for on the same destination. Both limits are settings of this instance, and both refusals answer 422.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $channelPathParam,
            'returns' => 'The channel, still unproven. The code itself is never returned: it goes to the destination and nowhere else.',
            'response' => ['data' => $sms],
        ],
        [
            'id' => 'notification-channels-proof-update',
            'returnFields' => ApiDocsFields::one('notification_channel', 'channel', $channelAttributes),
            'title' => 'Confirm a code',
            'label' => 'Confirm a code',
            'method' => 'PUT',
            'path' => '/notification-channels/{channel}/proof',
            'examplePath' => '/notification-channels/sms/proof',
            'description' => 'Type the code back to prove the destination is yours.',
            'body' => [
                'The three ways this can fail are said separately, because wrong, too late and too many tries ask three different things of whoever is reading. Each answers 422 with its own message.',
                'Wrong guesses are counted, and past the limit the code is spent whether or not the next guess would have been right. Ask for another one.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $channelPathParam,
            'bodyParams' => [
                [
                    'name' => 'code',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'The code that was sent to the destination, up to 16 characters.',
                ],
            ],
            'returns' => 'The channel with is_proven true. It still has to be switched on separately.',
            'response' => ['data' => $provenSms],
        ],
        [
            'id' => 'notification-channels-test-store',
            'responseStatus' => 201,
            'returnFields' => [
                ['name' => 'data', 'type' => 'object', 'description' => 'What happened when we tried.'],
                ['name' => 'data.sent', 'type' => 'boolean', 'description' => 'Whether the message got through.'],
                ['name' => 'data.reason', 'type' => 'string', 'description' => 'What the service said when it refused, up to 255 characters, or null when nothing refused.'],
            ],
            'title' => 'Send a test message',
            'label' => 'Send a test',
            'method' => 'POST',
            'path' => '/notification-channels/{channel}/test',
            'examplePath' => '/notification-channels/telegram/test',
            'description' => 'Send one message on a channel to find out whether it works.',
            'body' => [
                'This touches no reminder and writes no notification. Nothing here is something the account was owed, so nothing about it belongs in the record of what the account was told.',
                'A channel that is not finished is refused with 422. A channel that is finished but whose service refuses the message answers 422 as well, carrying what came back, and the refusal is counted against the channel exactly as a real send would be.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $channelPathParam,
            'returns' => 'Whether it got through, and what came back when it did not.',
            'response' => ['data' => ['sent' => true, 'reason' => null]],
        ],
        [
            'id' => 'notification-preferences-show',
            'returnFields' => ApiDocsFields::one('notification_preference', 'preference', $preferenceAttributes),
            'title' => 'Get notification preferences',
            'label' => 'Get preferences',
            'method' => 'GET',
            'path' => '/notification-preferences',
            'description' => 'Retrieve the quiet hours, the delivery mode and the zone both are read in.',
            'body' => [
                'These belong to the account rather than to a channel. Which channels are on is a separate thing, and is above.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'returns' => 'The preferences of the account. There is exactly one record, made with the account.',
            'response' => ['data' => $preference],
        ],
        [
            'id' => 'notification-preferences-update',
            'returnFields' => ApiDocsFields::one('notification_preference', 'preference', $preferenceAttributes),
            'title' => 'Change notification preferences',
            'label' => 'Change preferences',
            'method' => 'PUT',
            'path' => '/notification-preferences',
            'description' => 'Change the quiet hours, the delivery mode and the zone.',
            'body' => [
                'Quiet hours hold a notification back rather than dropping it: something due at three in the morning goes out when the quiet is over.',
                'Asking for a daily summary without an hour to send it at is refused with 422, and so is a time of day that is not one, or a zone the application does not know.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'bodyParams' => [
                [
                    'name' => 'quiet_hours_are_enabled',
                    'type' => 'boolean',
                    'required' => true,
                    'description' => 'Whether there are hours during which nothing may be delivered.',
                ],
                [
                    'name' => 'quiet_hours_start',
                    'type' => 'string',
                    'required' => false,
                    'description' => 'The local time from which nothing may go out, as HH:MM. Needed when quiet hours are on.',
                ],
                [
                    'name' => 'quiet_hours_end',
                    'type' => 'string',
                    'required' => false,
                    'description' => 'The local time from which delivery may resume, as HH:MM. Needed when quiet hours are on.',
                ],
                [
                    'name' => 'delivery_mode',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'Either immediate or digest.',
                ],
                [
                    'name' => 'digest_time',
                    'type' => 'string',
                    'required' => false,
                    'description' => 'The local time the daily summary is sent, as HH:MM. Needed when the mode is digest.',
                ],
                [
                    'name' => 'digest_weekdays',
                    'type' => 'array',
                    'required' => false,
                    'description' => 'Which days the summary is sent, as numbers from 0 for Sunday to 6 for Saturday. Left out, every day.',
                ],
                [
                    'name' => 'timezone',
                    'type' => 'string',
                    'required' => false,
                    'description' => 'The zone quiet hours and the summary hour are read in. Left out, whatever is saved stays.',
                ],
            ],
            'returns' => 'The preferences as they now stand.',
            'response' => ['data' => $preference],
        ],
    ],
];
