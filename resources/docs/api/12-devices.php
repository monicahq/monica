<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();
$dav = str_replace('/api/v1', '/dav', $base);

return [
    'name' => 'Contacts and calendar on a device',
    'guide' => true,
    'sections' => [
        [
            'id' => 'devices-address',
            'title' => 'What a device needs',
            'label' => 'What a device needs',
            'method' => 'GET',
            'path' => '/api-keys',
            'description' => 'Every vault of your account is an address book a contacts application can read over CardDAV, and two calendars a calendar application can read over CalDAV. Nothing of ours is installed on the device.',
            'body' => [
                'The address is '.$dav.', shown on the security screen beside your keys. The user name is your email address. The password is an API key, never your account password: a password would bypass your second factor, and somebody who signs in with a passkey has none.',
                'Make one key per device, so losing a phone costs you one key. A revoked key stops working on the very next request.',
                'A key reaches the fields marked sensitive only when it was minted with that choice, which is off by default, because an address book on a phone can be read by the other applications on it.',
            ],
            'rateLimit' => 'Sixty requests per minute, per key.',
        ],
        [
            'id' => 'devices-clients',
            'title' => 'Connecting each application',
            'label' => 'Connecting each application',
            'method' => 'GET',
            'path' => '/api-keys',
            'description' => 'The steps differ by application, and all of them ask for the same three things: the address, your email address and a key.',
            'body' => [
                'Apple Contacts and Calendar, on iOS and macOS: Settings, then Apps, then Contacts, then Accounts, then Add account, then Other, then Add CardDAV account. Use the address as the server. Add the calendars the same way through Add CalDAV account.',
                'Android, through DAVx5: add an account, choose Login with URL and user name, and give the address, your email address and the key. DAVx5 then lists the address books and calendars to sync.',
                'Thunderbird: Address Book, then File, then New, then CardDAV Address Book, and give the address and your email address. Calendars are added under Calendar, then New Calendar, then On the Network.',
                'GNOME Contacts and Evolution: Evolution, then File, then New, then Address Book, choose CardDAV, and give the address. GNOME Contacts reads the same account once Evolution holds it.',
                'KDE Kontact: Settings, then Configure Kontact, then Add Address Book, then DAV groupware resource, and give the address.',
            ],
            'rateLimit' => 'Sixty requests per minute, per key.',
        ],
        [
            'id' => 'devices-card',
            'title' => 'What a card carries',
            'label' => 'What a card carries',
            'method' => 'GET',
            'path' => '/api-keys',
            'description' => 'A card carries every part of the name, the nickname, the picture, and the value of every email, phone, website, postal address and recurring date field on the record, each labelled with the label of its field.',
            'body' => [
                'Nothing else from the record reaches a device. A field of another kind, a note, a document, a relationship: those are in the application and not on the card, because a device has nowhere to draw them.',
                'A date whose year nobody knows arrives without a year, rather than with a year the product invented.',
                'A record holds one value per field, so a device handing over three phone numbers where the structure holds one phone field writes the first to the record. The others stay on the card and still reach your other devices.',
            ],
            'rateLimit' => 'Sixty requests per minute, per key.',
        ],
        [
            'id' => 'devices-writing',
            'title' => 'What happens when a device writes',
            'label' => 'What happens when a device writes',
            'method' => 'POST',
            'path' => '/api-keys',
            'description' => 'A correction, a creation or a removal made on a device goes through the same operation the browser and this API call for the same gesture, and the account journal records it with the key as its source.',
            'body' => [
                'The card your device saves is kept whole. Anything on it the structure has no field for stays on the card rather than failing the save, and the card you read back is that same card with the record’s current values merged over it. Nothing a device wrote is lost, and nothing the application holds is stale.',
                'One card is kept per person, the one the last device sent. Removing a property on a device therefore removes it everywhere, and a client that drops what it does not understand drops it for your other devices too.',
                'A card saved into an address book creates a person in that vault, on the structure the vault starts new records from. A card carrying no name at all is refused.',
                'Deleting a card removes the person the way this API removes one: recoverable in the data and written to the journal. The contact representing you is refused, on a device as everywhere else.',
                'Saving over a card that changed since your device last read it is refused as a conflict, so the device reads the card again before writing and the change made in the browser is not overwritten.',
                'Dragging a card from one vault’s address book into another creates the person in the target vault and removes them from the source. Their reminders, their history and everything they took part in stay with the removed record, so what arrives is a new person carrying the card and nothing of their past. The journal records both halves.',
            ],
            'rateLimit' => 'Sixty requests per minute, per key.',
        ],
        [
            'id' => 'devices-calendars',
            'title' => 'The calendar and the task list',
            'label' => 'The calendar and the task list',
            'method' => 'GET',
            'path' => '/api-keys',
            'description' => 'Each vault offers a calendar of the recurring dates on its records, and a task list of the deadlines the reminders screen shows. Both are read only.',
            'body' => [
                'A recurring date is an all day entry that comes round every year, named after the person and the field it came from. A date whose year is unknown is placed in the current year.',
                'A deadline is a task due at the moment the product will act on it, which your device shows in whatever timezone it is set to. A postponed deadline moves to its new time; a dismissed or cancelled one is absent, and so is anything beyond the horizon the reminders screen shows.',
                'Every write to either is refused. Postponing and dismissing are gestures of the application with their own effects, and a date is corrected on the record it belongs to.',
            ],
            'rateLimit' => 'Sixty requests per minute, per key.',
        ],
    ],
];
