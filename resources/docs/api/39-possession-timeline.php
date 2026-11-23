<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();

$rateLimit = 'Sixty requests per minute, per user, shared by every key and device.';

$vaultSlug = 'dunder-mifflin';
$costumeId = '5e2c8a14-7d39-4b61-a0f3-c9e18b2d4f70';
$loanId = '8a3f1c60-2b47-4e9d-b1a5-7c0e9d3f2b48';
$maintenanceId = '2c9d4e71-6a15-4f38-9b02-e7f1a3c5d846';

$returned = [
    'type' => 'possession_timeline_entry',
    'attributes' => [
        'section' => 'loans',
        'kind' => 'loan_returned',
        'date' => '2026-11-02',
        'date_precision' => 'day',
        'is_current' => false,
        'is_acquisition' => false,
        'record_id' => $loanId,
    ],
    'links' => ['record' => "{$base}/vaults/{$vaultSlug}/possessions/{$costumeId}/loans/{$loanId}"],
];

$cleaning = [
    'type' => 'possession_timeline_entry',
    'attributes' => [
        'section' => 'maintenance',
        'kind' => 'maintenance',
        'date' => '2026-09-14',
        'date_precision' => 'day',
        'is_current' => false,
        'is_acquisition' => false,
        'record_id' => $maintenanceId,
    ],
    'links' => ['record' => "{$base}/vaults/{$vaultSlug}/possessions/{$costumeId}/maintenance-records/{$maintenanceId}"],
];

return [
    'name' => 'Possession timeline',
    'description' => 'Everything that happened to a possession, read from its sections and stored nowhere: the transactions, insurance records, valuations, provenance events, maintenance records, loans and stays, in one list. To change an entry, change the record it is read from.',
    'sections' => [
        [
            'id' => 'possession-timeline-index',
            'returnFields' => [
                ['name' => 'data', 'type' => 'array', 'description' => 'Every entry of the timeline, whole rather than a page at a time, the newest first and the undated last.'],
                ['name' => 'data[]', 'type' => 'object', 'description' => 'One entry.'],
                ['name' => 'data[].type', 'type' => 'string', 'description' => 'Always possession_timeline_entry. An entry is computed, so it has no id of its own.'],
                ['name' => 'data[].attributes', 'type' => 'object', 'description' => 'What the entry says.'],
                ['name' => 'data[].attributes.section', 'type' => 'string', 'description' => 'The section it is read from: transactions, insurance, valuations, provenance, maintenance, loans or locations.'],
                ['name' => 'data[].attributes.kind', 'type' => 'string', 'description' => 'What happened: transaction, insurance, valuation, provenance, maintenance, loan_started, loan_returned or stay. A loan that came back gives two entries, one of each loan kind.'],
                ['name' => 'data[].attributes.date', 'type' => 'string', 'description' => 'When it happened, written as 2026-11-02, or null when nobody knows.'],
                ['name' => 'data[].attributes.date_precision', 'type' => 'string', 'description' => 'How precise the date is: day, month, year, circa or unknown. Only a provenance event is ever less precise than a day.'],
                ['name' => 'data[].attributes.is_current', 'type' => 'boolean', 'description' => 'Whether this is the valuation giving the current value, or the stay where the possession is now.'],
                ['name' => 'data[].attributes.is_acquisition', 'type' => 'boolean', 'description' => 'Whether this transaction brought the possession in: a purchase, an exchange, a gift received or an inheritance.'],
                ['name' => 'data[].attributes.record_id', 'type' => 'uuid', 'description' => 'The id of the record the entry is read from.'],
                ['name' => 'data[].links', 'type' => 'object', 'description' => 'Where to find the record.'],
                ['name' => 'data[].links.record', 'type' => 'string', 'description' => 'The absolute address the record is read from, in its own section.'],
            ],
            'title' => 'List the timeline of a possession',
            'label' => 'List the timeline',
            'method' => 'GET',
            'path' => '/vaults/{vault}/possessions/{possession}/timeline',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$costumeId}/timeline",
            'description' => 'List everything that happened to a possession, the newest first.',
            'body' => [
                'An entry comes from each transaction, insurance record, valuation, provenance event, maintenance record and stay, and from each loan once it started and again once it came back. A planned loan gives none. A provenance event that a loan or a transaction wrote gives none, since the loan or the transaction already gives that moment.',
                'On the same day, the latest recorded comes first. Entries without a date come last.',
            ],
            'rateLimit' => $rateLimit,
            'pathParams' => [
                ['name' => 'vault', 'type' => 'string', 'required' => true, 'description' => 'The vault the possession is kept in, named either by the readable name it reports as slug or by its id. A vault of another account answers 404.'],
                ['name' => 'possession', 'type' => 'uuid', 'required' => true, 'description' => 'The id of the possession. A possession of another vault or of another account answers 404.'],
            ],
            'queryParams' => [
                ['name' => 'section', 'type' => 'string', 'required' => false, 'description' => 'Only the entries of this section: transactions, insurance, valuations, provenance, maintenance, loans or locations. Anything else answers 422.', 'example' => 'valuations'],
            ],
            'returns' => 'Every entry of the timeline, or an empty list. A vault or a possession of another account answers 404.',
            'response' => ['data' => [$returned, $cleaning]],
        ],
    ],
];
