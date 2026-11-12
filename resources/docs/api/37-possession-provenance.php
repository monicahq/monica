<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();
$application = rtrim((string) config('app.url'), '/');

$rateLimit = 'Sixty requests per minute, per user, shared by every key and device.';

$vaultSlug = 'schrute-farms';
$pressId = '5e1b7c93-4a28-4d6f-9b02-7c3e8a1f4d60';
$originId = '8a3f2d15-6c94-4e70-b1d8-3f5a9c2e7b41';
$giftId = 'c47e9b20-1d53-4a8f-9e6c-2b8d5f1a3e97';
$purchaseId = '2f6d8a41-9b37-4c05-a7e2-5d1c3b9f8e64';

$webLink = 'The Provenance section of the possession in the application, which opens for somebody signed in rather than for a key. It is the link to give a person.';

$origin = [
    'type' => 'possession_provenance_event',
    'id' => $originId,
    'attributes' => [
        'type' => 'origin',
        'group' => 'ownership',
        'title' => 'Built by Grandfather Schrute',
        'date_precision' => 'circa',
        'occurred_on' => '1921',
        'from' => null,
        'to' => 'Dwight Schrute Sr.',
        'location' => 'Honesdale',
        'description' => 'Mose remembers the cart it came on. No record has been found.',
        'reference_number' => null,
        'source_url' => null,
        'is_verified' => false,
        'transaction_id' => null,
        'created_at' => 1773446400,
    ],
    'links' => [
        'self' => "{$base}/vaults/{$vaultSlug}/possessions/{$pressId}/provenance/{$originId}",
        'web' => "{$application}/vaults/{$vaultSlug}/possessions/{$pressId}/provenance",
    ],
];

$gift = [
    'type' => 'possession_provenance_event',
    'id' => $giftId,
    'attributes' => [
        'type' => 'gift',
        'group' => 'ownership',
        'title' => 'Given to Dwight for his tenth birthday',
        'date_precision' => 'day',
        'occurred_on' => '1980-01-20',
        'from' => 'Dwight Schrute Sr.',
        'to' => 'Dwight Schrute',
        'location' => 'Schrute Farms',
        'description' => null,
        'reference_number' => 'Family ledger, page 12',
        'source_url' => null,
        'is_verified' => true,
        'transaction_id' => $purchaseId,
        'created_at' => 1773450000,
    ],
    'links' => [
        'self' => "{$base}/vaults/{$vaultSlug}/possessions/{$pressId}/provenance/{$giftId}",
        'web' => "{$application}/vaults/{$vaultSlug}/possessions/{$pressId}/provenance",
    ],
];

$attributes = [
    ['name' => 'type', 'type' => 'string', 'description' => 'What happened: origin, acquisition, inheritance, gift, sale, custody, loss, recovery, authentication, exhibition, publication or other.'],
    ['name' => 'group', 'type' => 'string', 'description' => 'The group the type belongs to: ownership, custody or authenticity.'],
    ['name' => 'title', 'type' => 'string', 'description' => 'What happened in a few words, or null, in which case the application shows the name of the type.'],
    ['name' => 'date_precision', 'type' => 'string', 'description' => 'How much of the date is known: day, month, year, circa or unknown.'],
    ['name' => 'occurred_on', 'type' => 'string', 'description' => 'When it happened, in the shape its precision gives: 1980-01-20 for a day, 1980-01 for a month, 1980 for a year or around a year, null when unknown.'],
    ['name' => 'from', 'type' => 'string', 'description' => 'Who it came from, as text, or null.'],
    ['name' => 'to', 'type' => 'string', 'description' => 'Who held it afterwards, as text, or null.'],
    ['name' => 'location', 'type' => 'string', 'description' => 'Where it happened, as text, or null.'],
    ['name' => 'description', 'type' => 'string', 'description' => 'What happened and how it is known, or null.'],
    ['name' => 'reference_number', 'type' => 'string', 'description' => 'The reference of the record (a lot, a deed, a certificate), or null.'],
    ['name' => 'source_url', 'type' => 'string', 'description' => 'Where the event can be checked, or null.'],
    ['name' => 'is_verified', 'type' => 'boolean', 'description' => 'Whether a document, a record or an expert confirms the event.'],
    ['name' => 'transaction_id', 'type' => 'uuid', 'description' => 'The identifier of the transaction of the same possession that records what the moment cost, or null. An event holds no money of its own.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the event was recorded, as a count of seconds since 1970.'],
];

$vaultParam = ['name' => 'vault', 'type' => 'string', 'required' => true, 'description' => 'The vault the possession is kept in, named either by the readable name it reports as slug or by the identifier it reports as id. A vault of another account answers 404.'];
$possessionParam = ['name' => 'possession', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of the possession. A possession of another vault or of another account answers 404.'];
$eventParam = ['name' => 'event', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of the event. An event of another possession answers 404.'];

$bodyParams = fn (bool $isCreating): array => [
    ['name' => 'type', 'type' => 'string', 'required' => $isCreating, 'description' => 'What happened: origin, acquisition, inheritance, gift, sale, custody, loss, recovery, authentication, exhibition, publication or other.', 'example' => 'gift'],
    ['name' => 'date_precision', 'type' => 'string', 'required' => false, 'default' => $isCreating ? 'day' : null, 'description' => 'How much of the date is known: day, month, year, circa or unknown.', 'example' => 'day'],
    ['name' => 'occurred_on', 'type' => 'string', 'required' => false, 'description' => 'When it happened, in the shape its precision gives: 1980-01-20, 1980-01 or 1980. A date in another shape is refused with “Write the date in the shape its precision asks for.” Ignored when the precision is unknown.', 'example' => '1980-01-20'],
    ['name' => 'title', 'type' => 'string', 'required' => false, 'description' => 'What happened in a few words, up to 255 characters. Markup is stripped.', 'example' => 'Given to Dwight for his tenth birthday'],
    ['name' => 'from', 'type' => 'string', 'required' => false, 'description' => 'Who it came from, up to 255 characters.', 'example' => 'Dwight Schrute Sr.'],
    ['name' => 'to', 'type' => 'string', 'required' => false, 'description' => 'Who held it afterwards, up to 255 characters.', 'example' => 'Dwight Schrute'],
    ['name' => 'location', 'type' => 'string', 'required' => false, 'description' => 'Where it happened, up to 255 characters.', 'example' => 'Schrute Farms'],
    ['name' => 'description', 'type' => 'string', 'required' => false, 'description' => 'What happened and how it is known, up to 5,000 characters.'],
    ['name' => 'reference_number', 'type' => 'string', 'required' => false, 'description' => 'The reference of the record, up to 255 characters.', 'example' => 'Family ledger, page 12'],
    ['name' => 'source_url', 'type' => 'string', 'required' => false, 'description' => 'A full web address where the event can be checked, up to 255 characters.'],
    ['name' => 'transaction_id', 'type' => 'uuid', 'required' => false, 'description' => 'The identifier of a transaction of the same possession that records what the moment cost. A transaction of another possession answers 422.', 'example' => $purchaseId],
    ['name' => 'is_verified', 'type' => 'boolean', 'required' => false, 'default' => $isCreating ? 'false' : null, 'description' => 'Whether a document, a record or an expert confirms the event.', 'example' => true],
];

$withoutNullDefaults = fn (array $params): array => array_map(
    fn (array $param): array => array_filter($param, fn (mixed $value, string $key): bool => $key !== 'default' || $value !== null, ARRAY_FILTER_USE_BOTH),
    $params,
);

return [
    'name' => 'Possession provenance',
    'description' => 'The moments in the history of a possession: where it was made, who owned or held it, when it was lost or recovered, and what authenticated, exhibited or published it. An event holds no money: a moment that cost something points at the transaction that records it.',
    'sections' => [
        [
            'id' => 'possession-provenance-index',
            'returnFields' => [
                ...ApiDocsFields::all('possession_provenance_event', 'possession provenance event', 'possession provenance events', $attributes),
                ['name' => 'data[].links.web', 'type' => 'string', 'description' => $webLink],
            ],
            'title' => 'List the provenance of a possession',
            'label' => 'List events',
            'method' => 'GET',
            'path' => '/vaults/{vault}/possessions/{possession}/provenance',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$pressId}/provenance",
            'description' => 'List every provenance event of a possession, oldest first.',
            'body' => ['Events with an unknown date come last. On the same date, the earliest recorded comes first. The list is whole rather than paginated.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam],
            'returns' => 'Every provenance event object of the possession, or an empty list. A vault or a possession of another account answers 404.',
            'response' => ['data' => [$origin, $gift]],
        ],
        [
            'id' => 'possession-provenance-show',
            'returnFields' => ApiDocsFields::one('possession_provenance_event', 'possession provenance event', $attributes, webLink: $webLink),
            'title' => 'Get a provenance event',
            'method' => 'GET',
            'path' => '/vaults/{vault}/possessions/{possession}/provenance/{event}',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$pressId}/provenance/{$giftId}",
            'description' => 'Retrieve one provenance event of a possession.',
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam, $eventParam],
            'returns' => 'A provenance event object. A vault, a possession or an event of another account answers 404.',
            'response' => ['data' => $gift],
        ],
        [
            'id' => 'possession-provenance-create',
            'returnFields' => ApiDocsFields::one('possession_provenance_event', 'possession provenance event', $attributes, webLink: $webLink),
            'title' => 'Record a provenance event',
            'method' => 'POST',
            'path' => '/vaults/{vault}/possessions/{possession}/provenance',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$pressId}/provenance",
            'description' => 'Record one moment in the provenance of a possession.',
            'body' => ['Only the type is required. The change is written to the account log.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam],
            'bodyParams' => $withoutNullDefaults($bodyParams(true)),
            'returns' => 'The provenance event object that was recorded.',
            'responseStatus' => 201,
            'response' => ['data' => $gift],
        ],
        [
            'id' => 'possession-provenance-update',
            'returnFields' => ApiDocsFields::one('possession_provenance_event', 'possession provenance event', $attributes, webLink: $webLink),
            'title' => 'Update a provenance event',
            'method' => 'PUT',
            'path' => '/vaults/{vault}/possessions/{possession}/provenance/{event}',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$pressId}/provenance/{$giftId}",
            'description' => 'Change a provenance event of a possession.',
            'body' => ['What you leave out keeps its value, and what you send as null is cleared. When the precision changes and no date is sent, the date is kept in the shape of the new precision. The change is written to the account log.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam, $eventParam],
            'bodyParams' => $withoutNullDefaults($bodyParams(false)),
            'exampleBody' => ['is_verified' => true],
            'returns' => 'The provenance event object, as it now stands. An event of another possession or of another account answers 404.',
            'response' => ['data' => $gift],
        ],
        [
            'id' => 'possession-provenance-destroy',
            'title' => 'Delete a provenance event',
            'method' => 'DELETE',
            'path' => '/vaults/{vault}/possessions/{possession}/provenance/{event}',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$pressId}/provenance/{$originId}",
            'description' => 'Delete a provenance event of a possession.',
            'body' => ['The deletion is final. The transaction the event points at, if any, stays. The account log keeps the name of the possession.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam, $eventParam],
            'returns' => 'Nothing. An event of another possession or of another account answers 404.',
            'responseStatus' => 204,
            'response' => [],
        ],
    ],
];
