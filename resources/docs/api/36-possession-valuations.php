<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();
$application = rtrim((string) config('app.url'), '/');

$rateLimit = 'Sixty requests per minute, per user, shared by every key and device.';

$vaultSlug = 'dunder-mifflin';
$dundieId = '9c4e2a71-5b38-4f06-8d1e-2a7f3c6b9e15';
$appraisalId = '3d7b1e94-8a2c-4f60-b5d3-6e19c0a7f248';
$estimateId = 'b81f5c3a-2d49-4e7b-9a06-f4c8e2d17b53';
$documentId = '6f2a9d48-1c7e-4b35-a8f0-2e5d9b4c7a16';

$webLink = 'The Valuations section of the possession in the application, which opens for somebody signed in rather than for a key. It is the link to give a person.';

$appraisal = [
    'type' => 'possession_valuation',
    'id' => $appraisalId,
    'attributes' => [
        'type' => 'appraisal',
        'valued_on' => '2026-03-14',
        'confidence' => 'certain',
        'amount' => '650.00',
        'currency' => 'usd',
        'is_current' => true,
        'valuer' => 'Scranton Jewelers',
        'method' => 'Inspection and comparable sales',
        'reference_number' => 'APP-2026-0212',
        'source_url' => 'https://scrantonjewelers.example.com/appraisals/0212',
        'note' => 'Michael insists the gold paint adds to its worth.',
        'documents' => [['id' => $documentId, 'name' => 'Appraisal certificate.pdf', 'mime_type' => 'application/pdf', 'size_in_bytes' => 240000]],
        'created_at' => 1773446400,
    ],
    'links' => [
        'self' => "{$base}/vaults/{$vaultSlug}/possessions/{$dundieId}/valuations/{$appraisalId}",
        'web' => "{$application}/vaults/{$vaultSlug}/possessions/{$dundieId}/valuations",
    ],
];

$estimate = [
    'type' => 'possession_valuation',
    'id' => $estimateId,
    'attributes' => [
        'type' => 'own_estimate',
        'valued_on' => '2019-11-20',
        'confidence' => 'uncertain',
        'amount' => '500.00',
        'currency' => 'usd',
        'is_current' => false,
        'valuer' => 'Michael Scott',
        'method' => null,
        'reference_number' => null,
        'source_url' => null,
        'note' => null,
        'documents' => [],
        'created_at' => 1761436800,
    ],
    'links' => [
        'self' => "{$base}/vaults/{$vaultSlug}/possessions/{$dundieId}/valuations/{$estimateId}",
        'web' => "{$application}/vaults/{$vaultSlug}/possessions/{$dundieId}/valuations",
    ],
];

$attributes = [
    ['name' => 'type', 'type' => 'string', 'description' => 'Where the figure comes from: own_estimate, appraisal, insurance_declaration, auction_result, comparable_listing or offer_received.'],
    ['name' => 'valued_on', 'type' => 'string', 'description' => 'The day the estimate applies to, written as 2026-03-14.'],
    ['name' => 'confidence', 'type' => 'string', 'description' => 'How sure somebody is of the figure: unknown, certain, probable or uncertain.'],
    ['name' => 'amount', 'type' => 'string', 'description' => 'What the possession is worth, as a string with two decimals.'],
    ['name' => 'currency', 'type' => 'string', 'description' => 'The currency of the amount, in lower case. Nothing is ever converted.'],
    ['name' => 'is_current', 'type' => 'boolean', 'description' => 'Whether this valuation gives the current value of the possession: the latest by date, the latest recorded on a tie.'],
    ['name' => 'valuer', 'type' => 'string', 'description' => 'Who estimated it, as text, or null.'],
    ['name' => 'method', 'type' => 'string', 'description' => 'How it was estimated, or null.'],
    ['name' => 'reference_number', 'type' => 'string', 'description' => 'The reference of the appraisal, the listing or the offer, or null.'],
    ['name' => 'source_url', 'type' => 'string', 'description' => 'Where the valuation can be checked, or null.'],
    ['name' => 'note', 'type' => 'string', 'description' => 'Anything worth remembering about the valuation, or null.'],
    ['name' => 'documents', 'type' => 'array', 'description' => 'The certificates, listings and offers attached to it, oldest first.'],
    ['name' => 'documents[]', 'type' => 'object', 'description' => 'One document.'],
    ['name' => 'documents[].id', 'type' => 'uuid', 'description' => 'The identifier of the file, which is what removing the document takes.'],
    ['name' => 'documents[].name', 'type' => 'string', 'description' => 'The name of the file as it was uploaded.'],
    ['name' => 'documents[].mime_type', 'type' => 'string', 'description' => 'The kind of the file, such as application/pdf.'],
    ['name' => 'documents[].size_in_bytes', 'type' => 'integer', 'description' => 'How much storage the file takes.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the valuation was recorded, as a count of seconds since 1970. A valuation is never changed, so it has no time of change.'],
];

$attributesWhenRecorded = array_values(array_filter($attributes, fn (array $field): bool => ! str_starts_with($field['name'], 'documents[]')));

$recorded = ['data' => [...$appraisal, 'attributes' => [...$appraisal['attributes'], 'documents' => []]]];

$vaultParam = ['name' => 'vault', 'type' => 'string', 'required' => true, 'description' => 'The vault the possession is kept in, named either by the readable name it reports as slug or by the identifier it reports as id. A vault of another account answers 404.'];
$possessionParam = ['name' => 'possession', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of the possession. A possession of another vault or of another account answers 404.'];
$valuationParam = ['name' => 'valuation', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of the valuation. A valuation of another possession answers 404.'];

return [
    'name' => 'Possession valuations',
    'description' => 'Estimates of what a possession is worth, each on one date. A valuation is never changed once recorded: a correction is a new valuation, so the earlier ones stay as the history of how the value moved. The latest by date is the current value of the possession.',
    'sections' => [
        [
            'id' => 'possession-valuations-index',
            'returnFields' => [
                ...ApiDocsFields::all('possession_valuation', 'possession valuation', 'possession valuations', $attributes),
                ['name' => 'data[].links.web', 'type' => 'string', 'description' => $webLink],
            ],
            'title' => 'List the valuations of a possession',
            'label' => 'List valuations',
            'method' => 'GET',
            'path' => '/vaults/{vault}/possessions/{possession}/valuations',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$dundieId}/valuations",
            'description' => 'List every valuation of a possession, the latest by date first.',
            'body' => ['On the same day, the latest recorded comes first. The first one is the current value. The list is whole rather than paginated.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam],
            'returns' => 'Every valuation object of the possession, or an empty list. A vault or a possession of another account answers 404.',
            'response' => ['data' => [$appraisal, $estimate]],
        ],
        [
            'id' => 'possession-valuations-show',
            'returnFields' => ApiDocsFields::one('possession_valuation', 'possession valuation', $attributes, webLink: $webLink),
            'title' => 'Get a valuation',
            'method' => 'GET',
            'path' => '/vaults/{vault}/possessions/{possession}/valuations/{valuation}',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$dundieId}/valuations/{$appraisalId}",
            'description' => 'Retrieve one valuation of a possession.',
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam, $valuationParam],
            'returns' => 'A valuation object. A vault, a possession or a valuation of another account answers 404.',
            'response' => ['data' => $appraisal],
        ],
        [
            'id' => 'possession-valuations-create',
            'returnFields' => ApiDocsFields::one('possession_valuation', 'possession valuation', $attributesWhenRecorded, webLink: $webLink),
            'title' => 'Record a valuation',
            'method' => 'POST',
            'path' => '/vaults/{vault}/possessions/{possession}/valuations',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$dundieId}/valuations",
            'description' => 'Record what a possession is worth on a date.',
            'body' => [
                'The type, the date, the amount and its currency are required. There is no endpoint to change a valuation: to correct one, record a new valuation.',
                'Documents are attached afterwards, one at a time, with the endpoint below. The change is written to the account log.',
            ],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam],
            'bodyParams' => [
                ['name' => 'type', 'type' => 'string', 'required' => true, 'description' => 'Where the figure comes from: own_estimate, appraisal, insurance_declaration, auction_result, comparable_listing or offer_received.', 'example' => 'appraisal'],
                ['name' => 'valued_on', 'type' => 'string', 'required' => true, 'description' => 'The day the estimate applies to, written as 2026-03-14.', 'example' => '2026-03-14'],
                ['name' => 'amount', 'type' => 'number', 'required' => true, 'description' => 'What the possession is worth. Zero or more, up to 9999999999.99. A figure below zero is refused with “Write an amount of zero or more.”', 'example' => 650],
                ['name' => 'currency', 'type' => 'string', 'required' => true, 'description' => 'The currency of the amount: usd, eur, gbp, cad, aud, chf, jpy, brl, inr or cny.', 'example' => 'usd'],
                ['name' => 'confidence', 'type' => 'string', 'required' => false, 'default' => 'unknown', 'description' => 'How sure somebody is of the figure: unknown, certain, probable or uncertain.', 'example' => 'certain'],
                ['name' => 'valuer', 'type' => 'string', 'required' => false, 'description' => 'Who estimated it, up to 255 characters. Markup is stripped.', 'example' => 'Scranton Jewelers'],
                ['name' => 'method', 'type' => 'string', 'required' => false, 'description' => 'How it was estimated, up to 255 characters.', 'example' => 'Inspection and comparable sales'],
                ['name' => 'reference_number', 'type' => 'string', 'required' => false, 'description' => 'The reference of the appraisal, the listing or the offer, up to 255 characters.', 'example' => 'APP-2026-0212'],
                ['name' => 'source_url', 'type' => 'string', 'required' => false, 'description' => 'A full web address where the valuation can be checked, up to 255 characters.', 'example' => 'https://scrantonjewelers.example.com/appraisals/0212'],
                ['name' => 'note', 'type' => 'string', 'required' => false, 'description' => 'Anything worth remembering about the valuation, up to 5,000 characters.', 'example' => 'Michael insists the gold paint adds to its worth.'],
            ],
            'returns' => 'The valuation object that was recorded.',
            'responseStatus' => 201,
            'response' => $recorded,
        ],
        [
            'id' => 'possession-valuations-destroy',
            'title' => 'Delete a valuation',
            'method' => 'DELETE',
            'path' => '/vaults/{vault}/possessions/{possession}/valuations/{valuation}',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$dundieId}/valuations/{$estimateId}",
            'description' => 'Delete a valuation of a possession.',
            'body' => ['The deletion is final. When the valuation gave the current value, the latest one left gives it instead. Its documents are let go and swept up after a grace period once nothing uses them. The account log keeps the name of the possession.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam, $valuationParam],
            'returns' => 'Nothing. A valuation of another possession or of another account answers 404.',
            'responseStatus' => 204,
            'response' => [],
        ],
        [
            'id' => 'possession-valuation-documents-create',
            'flag' => ['file-upload'],
            'returnFields' => ApiDocsFields::one('possession_valuation', 'possession valuation', $attributes, webLink: $webLink),
            'title' => 'Attach a document to a valuation',
            'label' => 'Attach a document',
            'method' => 'POST',
            'path' => '/vaults/{vault}/possessions/{possession}/valuations/{valuation}/documents',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$dundieId}/valuations/{$appraisalId}/documents",
            'description' => 'Upload a certificate, a listing or an offer and attach it to a valuation. The change is written to the account log.',
            'body' => [
                'This endpoint exists only while file uploads are switched on for the account. Otherwise it answers 404.',
                'Send the file as multipart form data, in a field called file. The type of the file is not restricted, and it counts against the storage of the account.',
                'An upload larger than the size this instance allows answers 422 on file, and so does an upload that would take the account past the storage it has left. In both cases nothing is kept.',
            ],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam, $valuationParam],
            'bodyParams' => [
                ['name' => 'file', 'type' => 'file', 'required' => true, 'description' => 'The document to attach. A request without one answers 422 on file.'],
            ],
            'returns' => 'The valuation object, with the document in its documents. A valuation of another possession or of another account answers 404.',
            'responseStatus' => 201,
            'response' => ['data' => $appraisal],
        ],
        [
            'id' => 'possession-valuation-documents-destroy',
            'title' => 'Remove a document from a valuation',
            'label' => 'Remove a document',
            'method' => 'DELETE',
            'path' => '/vaults/{vault}/possessions/{possession}/valuations/{valuation}/documents/{file}',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$dundieId}/valuations/{$appraisalId}/documents/{$documentId}",
            'description' => 'Remove a document from a valuation. The change is written to the account log.',
            'body' => ['The file is let go rather than deleted outright. It stays wherever else it is used, and is swept up after a grace period once nothing uses it.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [
                $vaultParam,
                $possessionParam,
                $valuationParam,
                ['name' => 'file', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of the document, as the documents of the valuation give it. A document of another valuation answers 404.'],
            ],
            'returns' => 'Nothing. A valuation of another possession, a document of another valuation or of another account answers 404.',
            'responseStatus' => 204,
            'response' => [],
        ],
    ],
];
