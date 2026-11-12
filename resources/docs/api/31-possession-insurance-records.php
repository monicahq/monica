<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();
$application = rtrim((string) config('app.url'), '/');

$rateLimit = 'Sixty requests per minute, per user, shared by every key and device.';

$vaultSlug = 'dunder-mifflin';
$dundieId = '9c4e2a71-5b38-4f06-8d1e-2a7f3c6b9e15';
$sabreId = '5a2e8c14-6b3f-4d71-9e08-c7f1a3b5d2e6';
$uticaId = 'e09b4d27-3c58-4a1f-8b62-1d7e5f9a0c34';

$webLink = 'The Insurance section of the possession in the application, which opens for somebody signed in rather than for a key. It is the link to give a person.';

$sabre = [
    'type' => 'possession_insurance_record',
    'id' => $sabreId,
    'attributes' => [
        'insurer' => 'Sabre Insurance',
        'status' => 'active',
        'is_current' => true,
        'policy_number' => 'SAB-2026-0042',
        'coverage' => 'Theft, fire and accidental damage',
        'insured_value' => '500.00',
        'excess' => '50.00',
        'currency' => 'usd',
        'starts_on' => '2026-01-01',
        'ends_on' => '2027-01-01',
        'is_listed_individually' => true,
        'contact_name' => 'Gabe Lewis',
        'contact_email' => 'gabe@sabre.example.com',
        'contact_phone' => null,
        'note' => 'Not covered when Michael lends it to Dwight.',
        'created_at' => 1761436800,
        'updated_at' => 1761436800,
    ],
    'links' => [
        'self' => "{$base}/vaults/{$vaultSlug}/possessions/{$dundieId}/insurance-records/{$sabreId}",
        'web' => "{$application}/vaults/{$vaultSlug}/possessions/{$dundieId}/insurance",
    ],
];

$utica = [
    'type' => 'possession_insurance_record',
    'id' => $uticaId,
    'attributes' => [
        'insurer' => 'Utica Assurance',
        'status' => 'expired',
        'is_current' => false,
        'policy_number' => null,
        'coverage' => 'Office contents',
        'insured_value' => '300.00',
        'excess' => null,
        'currency' => 'usd',
        'starts_on' => '2019-11-05',
        'ends_on' => '2025-12-31',
        'is_listed_individually' => false,
        'contact_name' => null,
        'contact_email' => null,
        'contact_phone' => null,
        'note' => null,
        'created_at' => 1761440400,
        'updated_at' => 1761440400,
    ],
    'links' => [
        'self' => "{$base}/vaults/{$vaultSlug}/possessions/{$dundieId}/insurance-records/{$uticaId}",
        'web' => "{$application}/vaults/{$vaultSlug}/possessions/{$dundieId}/insurance",
    ],
];

$attributes = [
    ['name' => 'insurer', 'type' => 'string', 'description' => 'Who insures the possession, as text.'],
    ['name' => 'status', 'type' => 'string', 'description' => 'Where the policy stands: active, pending, expired or cancelled. It is the one entered and never follows the dates.'],
    ['name' => 'is_current', 'type' => 'boolean', 'description' => 'Whether this is the record the possession is insured under now: the active one with the latest start.'],
    ['name' => 'policy_number', 'type' => 'string', 'description' => 'The number of the policy, or null.'],
    ['name' => 'coverage', 'type' => 'string', 'description' => 'What the policy covers, or null.'],
    ['name' => 'insured_value', 'type' => 'string', 'description' => 'The value the possession is insured for, as a string with two decimals.'],
    ['name' => 'excess', 'type' => 'string', 'description' => 'What is paid before the insurer pays, or null when it is not recorded.'],
    ['name' => 'currency', 'type' => 'string', 'description' => 'The currency of the insured value and the excess, in lower case. Nothing is ever converted.'],
    ['name' => 'starts_on', 'type' => 'string', 'description' => 'The day the policy period starts, written as 2026-01-01, or null.'],
    ['name' => 'ends_on', 'type' => 'string', 'description' => 'The day the policy period ends or renews, or null for a policy with no end date.'],
    ['name' => 'is_listed_individually', 'type' => 'boolean', 'description' => 'Whether the policy names this possession, rather than covering it under the general contents of the home.'],
    ['name' => 'contact_name', 'type' => 'string', 'description' => 'Who to call at the insurer, or null.'],
    ['name' => 'contact_email', 'type' => 'string', 'description' => 'The email of that person, or null.'],
    ['name' => 'contact_phone', 'type' => 'string', 'description' => 'The phone of that person, or null.'],
    ['name' => 'note', 'type' => 'string', 'description' => 'Conditions, exclusions, what the insurer asked for, or null.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the record was added, as a count of seconds since 1970.'],
    ['name' => 'updated_at', 'type' => 'timestamp', 'description' => 'When it last changed, as a count of seconds, or null.'],
];

$vaultParam = ['name' => 'vault', 'type' => 'string', 'required' => true, 'description' => 'The vault the possession is kept in, named either by the readable name it reports as slug or by the identifier it reports as id. A vault of another account answers 404.'];
$possessionParam = ['name' => 'possession', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of the possession. A possession of another vault or of another account answers 404.'];
$recordParam = ['name' => 'insuranceRecord', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of the insurance record. A record of another possession answers 404.'];

$figure = 'Zero or more, up to 9999999999.99. A figure below zero is refused with “Write an amount of zero or more.”';

$optionalParams = [
    ['name' => 'policy_number', 'type' => 'string', 'required' => false, 'description' => 'The number of the policy, up to 255 characters.', 'example' => 'SAB-2026-0042'],
    ['name' => 'coverage', 'type' => 'string', 'required' => false, 'description' => 'What the policy covers, up to 255 characters.', 'example' => 'Theft, fire and accidental damage'],
    ['name' => 'excess', 'type' => 'number', 'required' => false, 'description' => "What is paid before the insurer pays. {$figure}", 'example' => 50],
    ['name' => 'starts_on', 'type' => 'string', 'required' => false, 'description' => 'The day the policy period starts, written as 2026-01-01.', 'example' => '2026-01-01'],
    ['name' => 'ends_on', 'type' => 'string', 'required' => false, 'description' => 'The day the policy period ends or renews, written as 2027-01-01. A day before the start is refused with “The end cannot come before the start.”', 'example' => '2027-01-01'],
    ['name' => 'is_listed_individually', 'type' => 'boolean', 'required' => false, 'description' => 'Whether the policy names this possession rather than the general contents of the home. False when left out on creation.', 'example' => true],
    ['name' => 'contact_name', 'type' => 'string', 'required' => false, 'description' => 'Who to call at the insurer, up to 255 characters.', 'example' => 'Gabe Lewis'],
    ['name' => 'contact_email', 'type' => 'string', 'required' => false, 'description' => 'The email of that person, a full email address.', 'example' => 'gabe@sabre.example.com'],
    ['name' => 'contact_phone', 'type' => 'string', 'required' => false, 'description' => 'The phone of that person, up to 50 characters.'],
    ['name' => 'note', 'type' => 'string', 'required' => false, 'description' => 'Conditions, exclusions, what the insurer asked for, up to 5,000 characters.', 'example' => 'Not covered when Michael lends it to Dwight.'],
];

return [
    'name' => 'Possession insurance records',
    'description' => 'How a possession is insured, one record per policy period. When a policy renews with a new insured value, or the insurer changes, a new record is added, and the earlier ones stay as the history of the coverage.',
    'sections' => [
        [
            'id' => 'possession-insurance-records-index',
            'returnFields' => [
                ...ApiDocsFields::all('possession_insurance_record', 'possession insurance record', 'possession insurance records', $attributes),
                ['name' => 'data[].links.web', 'type' => 'string', 'description' => $webLink],
            ],
            'title' => 'List the insurance records of a possession',
            'label' => 'List insurance records',
            'method' => 'GET',
            'path' => '/vaults/{vault}/possessions/{possession}/insurance-records',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$dundieId}/insurance-records",
            'description' => 'List every insurance record of a possession, active first.',
            'body' => ['Active records come first, then pending, expired and cancelled ones. Within each status the latest start comes first, an undated record after the dated ones. The list is whole rather than paginated.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam],
            'returns' => 'Every insurance record object of the possession, or an empty list. A vault or a possession of another account answers 404.',
            'response' => ['data' => [$sabre, $utica]],
        ],
        [
            'id' => 'possession-insurance-records-show',
            'returnFields' => ApiDocsFields::one('possession_insurance_record', 'possession insurance record', $attributes, webLink: $webLink),
            'title' => 'Get an insurance record',
            'method' => 'GET',
            'path' => '/vaults/{vault}/possessions/{possession}/insurance-records/{insuranceRecord}',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$dundieId}/insurance-records/{$sabreId}",
            'description' => 'Retrieve one insurance record of a possession.',
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam, $recordParam],
            'returns' => 'An insurance record object. A vault, a possession or a record of another account answers 404.',
            'response' => ['data' => $sabre],
        ],
        [
            'id' => 'possession-insurance-records-create',
            'returnFields' => ApiDocsFields::one('possession_insurance_record', 'possession insurance record', $attributes, webLink: $webLink),
            'title' => 'Add an insurance record',
            'method' => 'POST',
            'path' => '/vaults/{vault}/possessions/{possession}/insurance-records',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$dundieId}/insurance-records",
            'description' => 'Record one policy period covering a possession.',
            'body' => [
                'The insurer, the status, the insured value and its currency are required.',
                'The change is written to the account log.',
            ],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam],
            'bodyParams' => [
                ['name' => 'insurer', 'type' => 'string', 'required' => true, 'description' => 'Who insures the possession, up to 255 characters. Markup is stripped.', 'example' => 'Sabre Insurance'],
                ['name' => 'status', 'type' => 'string', 'required' => true, 'description' => 'Where the policy stands: active, pending, expired or cancelled.', 'example' => 'active'],
                ['name' => 'insured_value', 'type' => 'number', 'required' => true, 'description' => "The value the possession is insured for. {$figure}", 'example' => 500],
                ['name' => 'currency', 'type' => 'string', 'required' => true, 'description' => 'The currency of the insured value and the excess: usd, eur, gbp, cad, aud, chf, jpy, brl, inr or cny.', 'example' => 'usd'],
                ...$optionalParams,
            ],
            'returns' => 'The insurance record object that was added.',
            'responseStatus' => 201,
            'response' => ['data' => $sabre],
        ],
        [
            'id' => 'possession-insurance-records-update',
            'returnFields' => ApiDocsFields::one('possession_insurance_record', 'possession insurance record', $attributes, webLink: $webLink),
            'title' => 'Change an insurance record',
            'method' => 'PUT',
            'path' => '/vaults/{vault}/possessions/{possession}/insurance-records/{insuranceRecord}',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$dundieId}/insurance-records/{$sabreId}",
            'description' => 'Change what one insurance record says, under the rules that recorded it.',
            'body' => [
                'A field left out of the request keeps its value, and a field sent as null is cleared. The other records are not touched.',
                'The change is written to the account log.',
            ],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam, $recordParam],
            'bodyParams' => [
                ['name' => 'insurer', 'type' => 'string', 'required' => false, 'description' => 'Who insures the possession, up to 255 characters.'],
                ['name' => 'status', 'type' => 'string', 'required' => false, 'description' => 'Where the policy stands: active, pending, expired or cancelled.', 'example' => 'expired'],
                ['name' => 'insured_value', 'type' => 'number', 'required' => false, 'description' => "The value the possession is insured for. {$figure}"],
                ['name' => 'currency', 'type' => 'string', 'required' => false, 'description' => 'The currency of the insured value and the excess.'],
                ...$optionalParams,
            ],
            'returns' => 'The insurance record object as it now reads. A record of another possession or of another account answers 404.',
            'response' => ['data' => $sabre],
        ],
        [
            'id' => 'possession-insurance-records-destroy',
            'title' => 'Delete an insurance record',
            'method' => 'DELETE',
            'path' => '/vaults/{vault}/possessions/{possession}/insurance-records/{insuranceRecord}',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$dundieId}/insurance-records/{$uticaId}",
            'description' => 'Delete an insurance record of a possession.',
            'body' => ['The deletion is final, and the history of the coverage loses that policy period. The account log keeps the name of the possession.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam, $recordParam],
            'returns' => 'Nothing. A record of another possession or of another account answers 404.',
            'responseStatus' => 204,
            'response' => [],
        ],
    ],
];
