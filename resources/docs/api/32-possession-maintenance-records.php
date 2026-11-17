<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();
$application = rtrim((string) config('app.url'), '/');

$rateLimit = 'Sixty requests per minute, per user, shared by every key and device.';

$vaultSlug = 'dunder-mifflin';
$dundieId = '9c4e2a71-5b38-4f06-8d1e-2a7f3c6b9e15';
$replatingId = '3f8b1d62-7a4c-4e95-b2d0-6c1e9a5f7b38';
$inspectionId = 'b71c5e09-2d36-4f8a-9e14-0a6d3b8c2f57';
$wornId = 'd4a91f3e-6b27-4c58-8e0d-1f5a7c3b9e62';
$likeNewId = '6e2c8b47-1a95-4d3f-b706-9c4e2f1a8d53';

$webLink = 'The Maintenance section of the possession in the application, which opens for somebody signed in rather than for a key. It is the link to give a person.';

$replating = [
    'type' => 'possession_maintenance_record',
    'id' => $replatingId,
    'attributes' => [
        'maintenance_type' => 'restoration',
        'title' => 'Re-plating',
        'performed_by' => 'Scranton Trophy and Engraving',
        'performed_on' => '2026-03-12',
        'cost' => '40.00',
        'currency' => 'usd',
        'condition_before' => ['id' => $wornId, 'name' => 'Worn'],
        'condition_after' => ['id' => $likeNewId, 'name' => 'Like new'],
        'next_due_on' => '2027-03-12',
        'is_overdue' => false,
        'description' => 'The gold finish had worn through on the base. Michael wants it ready for the next Dundies.',
        'created_at' => 1773331200,
        'updated_at' => 1773331200,
    ],
    'links' => [
        'self' => "{$base}/vaults/{$vaultSlug}/possessions/{$dundieId}/maintenance-records/{$replatingId}",
        'web' => "{$application}/vaults/{$vaultSlug}/possessions/{$dundieId}/maintenance",
    ],
];

$inspection = [
    'type' => 'possession_maintenance_record',
    'id' => $inspectionId,
    'attributes' => [
        'maintenance_type' => 'inspection',
        'title' => 'Engraving check',
        'performed_by' => null,
        'performed_on' => '2025-02-14',
        'cost' => null,
        'currency' => null,
        'condition_before' => ['id' => $wornId, 'name' => 'Worn'],
        'condition_after' => null,
        'next_due_on' => '2026-02-14',
        'is_overdue' => false,
        'description' => null,
        'created_at' => 1739545200,
        'updated_at' => 1739545200,
    ],
    'links' => [
        'self' => "{$base}/vaults/{$vaultSlug}/possessions/{$dundieId}/maintenance-records/{$inspectionId}",
        'web' => "{$application}/vaults/{$vaultSlug}/possessions/{$dundieId}/maintenance",
    ],
];

$attributes = [
    ['name' => 'maintenance_type', 'type' => 'string', 'description' => 'What kind of job it was: inspection, cleaning, servicing, repair, restoration, part_replacement, conservation or other.'],
    ['name' => 'title', 'type' => 'string', 'description' => 'What was done, as text.'],
    ['name' => 'performed_by', 'type' => 'string', 'description' => 'The person or the workshop who did the job, as text, or null.'],
    ['name' => 'performed_on', 'type' => 'string', 'description' => 'The day the job was done, written as 2026-03-12.'],
    ['name' => 'cost', 'type' => 'string', 'description' => 'What the job cost, as a string with two decimals, or null when it is not recorded. It is never added to the transactions or to the declared value of the possession.'],
    ['name' => 'currency', 'type' => 'string', 'description' => 'The currency of the cost, in lower case, or null when there is no cost. Nothing is ever converted.'],
    ['name' => 'condition_before', 'type' => 'object', 'description' => 'The possession condition the possession was in before the job, or null when it is not recorded. A condition renamed since gives its new name, and one deleted since leaves null.'],
    ['name' => 'condition_before.id', 'type' => 'uuid', 'description' => 'The id of the condition, as the possession conditions endpoints give it.'],
    ['name' => 'condition_before.name', 'type' => 'string', 'description' => 'What the condition is called.'],
    ['name' => 'condition_after', 'type' => 'object', 'description' => 'The possession condition the possession was in after the job, or null when it is not recorded. A condition renamed since gives its new name, and one deleted since leaves null.'],
    ['name' => 'condition_after.id', 'type' => 'uuid', 'description' => 'The id of the condition, as the possession conditions endpoints give it.'],
    ['name' => 'condition_after.name', 'type' => 'string', 'description' => 'What the condition is called.'],
    ['name' => 'next_due_on', 'type' => 'string', 'description' => 'The day the job should be done again, or null when nothing is scheduled.'],
    ['name' => 'is_overdue', 'type' => 'boolean', 'description' => 'Whether the job is due again and has not been done. Only the latest record of the possession can be overdue, since a later job replaces the schedule of an earlier one, and it is overdue when its next due date is before today, in the timezone of the user the key belongs to. It is read, never written.'],
    ['name' => 'description', 'type' => 'string', 'description' => 'What was found and what was done, or null.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the record was added, as a count of seconds since 1970.'],
    ['name' => 'updated_at', 'type' => 'timestamp', 'description' => 'When it last changed, as a count of seconds, or null.'],
];

$vaultParam = ['name' => 'vault', 'type' => 'string', 'required' => true, 'description' => 'The vault the possession is kept in, named either by the readable name it reports as slug or by its id. A vault of another account answers 404.'];
$possessionParam = ['name' => 'possession', 'type' => 'uuid', 'required' => true, 'description' => 'The id of the possession. A possession of another vault or of another account answers 404.'];
$recordParam = ['name' => 'maintenanceRecord', 'type' => 'uuid', 'required' => true, 'description' => 'The id of the maintenance record. A record of another possession answers 404.'];

$types = 'inspection, cleaning, servicing, repair, restoration, part_replacement, conservation or other';
$title = 'What was done, up to 255 characters. Markup is stripped, and a title left empty once it is stripped is refused with “Say what was done.”';
$performedOn = 'The day the job was done, written as 2026-03-12. A day after today, in the timezone of the user the key belongs to, is refused with “The job cannot be performed in the future.”';
$condition = 'The id of one of the possession conditions of your account, or null for none. A condition of another account answers 404.';

$optionalParams = [
    ['name' => 'performed_by', 'type' => 'string', 'required' => false, 'description' => 'The person or the workshop who did the job, up to 255 characters.', 'example' => 'Scranton Trophy and Engraving'],
    ['name' => 'cost', 'type' => 'number', 'required' => false, 'description' => 'What the job cost. Zero or more, up to 9999999999.99. A figure below zero is refused.', 'example' => 40],
    ['name' => 'currency', 'type' => 'string', 'required' => false, 'description' => 'The currency of the cost: usd, eur, gbp, cad, aud, chf, jpy, brl, inr or cny. A cost sent without one is refused with “Choose the currency of the cost.”', 'example' => 'usd'],
    ['name' => 'condition_before_id', 'type' => 'uuid', 'required' => false, 'description' => "The condition the possession was in before the job. {$condition}", 'example' => $wornId],
    ['name' => 'condition_after_id', 'type' => 'uuid', 'required' => false, 'description' => "The condition the possession was in after the job. {$condition}", 'example' => $likeNewId],
    ['name' => 'next_due_on', 'type' => 'string', 'required' => false, 'description' => 'The day the job should be done again, written as 2027-03-12. A day before the job is refused with “The next due date cannot come before the job.”', 'example' => '2027-03-12'],
    ['name' => 'description', 'type' => 'string', 'required' => false, 'description' => 'What was found and what was done, up to 5,000 characters. Markup is stripped.', 'example' => 'The gold finish had worn through on the base. Michael wants it ready for the next Dundies.'],
];

return [
    'name' => 'Possession maintenance records',
    'description' => 'The work done on a possession over time, one record per job: an inspection, a cleaning, a repair, a restoration. Each record may say what condition the possession was in before and after the job, and when the job should be done again.',
    'sections' => [
        [
            'id' => 'possession-maintenance-records-index',
            'returnFields' => [
                ...ApiDocsFields::all('possession_maintenance_record', 'possession maintenance record', 'possession maintenance records', $attributes),
                ['name' => 'data[].links.web', 'type' => 'string', 'description' => $webLink],
            ],
            'title' => 'List the maintenance records of a possession',
            'label' => 'List maintenance records',
            'method' => 'GET',
            'path' => '/vaults/{vault}/possessions/{possession}/maintenance-records',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$dundieId}/maintenance-records",
            'description' => 'List every maintenance record of a possession, the latest job first.',
            'body' => ['The record with the latest day performed comes first, and on the same day the one added last comes first. The first record is therefore the only one that can be overdue. The list is whole rather than paginated.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam],
            'returns' => 'Every maintenance record object of the possession, or an empty list. A vault or a possession of another account answers 404.',
            'response' => ['data' => [$replating, $inspection]],
        ],
        [
            'id' => 'possession-maintenance-records-show',
            'returnFields' => ApiDocsFields::one('possession_maintenance_record', 'possession maintenance record', $attributes, webLink: $webLink),
            'title' => 'Get a maintenance record',
            'method' => 'GET',
            'path' => '/vaults/{vault}/possessions/{possession}/maintenance-records/{maintenanceRecord}',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$dundieId}/maintenance-records/{$replatingId}",
            'description' => 'Retrieve one maintenance record of a possession.',
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam, $recordParam],
            'returns' => 'A maintenance record object. A vault, a possession or a record of another account answers 404.',
            'response' => ['data' => $replating],
        ],
        [
            'id' => 'possession-maintenance-records-create',
            'returnFields' => ApiDocsFields::one('possession_maintenance_record', 'possession maintenance record', $attributes, webLink: $webLink),
            'title' => 'Add a maintenance record',
            'method' => 'POST',
            'path' => '/vaults/{vault}/possessions/{possession}/maintenance-records',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$dundieId}/maintenance-records",
            'description' => 'Record one job done on a possession.',
            'body' => [
                'The type, the title and the day performed are required.',
                'The change is written to the account log.',
            ],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam],
            'bodyParams' => [
                ['name' => 'maintenance_type', 'type' => 'string', 'required' => true, 'description' => "What kind of job it was: {$types}.", 'example' => 'restoration'],
                ['name' => 'title', 'type' => 'string', 'required' => true, 'description' => $title, 'example' => 'Re-plating'],
                ['name' => 'performed_on', 'type' => 'string', 'required' => true, 'description' => $performedOn, 'example' => '2026-03-12'],
                ...$optionalParams,
            ],
            'returns' => 'The maintenance record object that was added. A condition of another account answers 404.',
            'responseStatus' => 201,
            'response' => ['data' => $replating],
        ],
        [
            'id' => 'possession-maintenance-records-update',
            'returnFields' => ApiDocsFields::one('possession_maintenance_record', 'possession maintenance record', $attributes, webLink: $webLink),
            'title' => 'Change a maintenance record',
            'method' => 'PUT',
            'path' => '/vaults/{vault}/possessions/{possession}/maintenance-records/{maintenanceRecord}',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$dundieId}/maintenance-records/{$replatingId}",
            'description' => 'Change what one maintenance record says, under the rules that recorded it.',
            'body' => [
                'A field left out of the request keeps its value, and an optional field sent as null is cleared. The other records are not touched, but which record is overdue follows the change.',
                'The change is written to the account log.',
            ],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam, $recordParam],
            'bodyParams' => [
                ['name' => 'maintenance_type', 'type' => 'string', 'required' => false, 'description' => "What kind of job it was: {$types}."],
                ['name' => 'title', 'type' => 'string', 'required' => false, 'description' => $title],
                ['name' => 'performed_on', 'type' => 'string', 'required' => false, 'description' => $performedOn],
                ...$optionalParams,
            ],
            'returns' => 'The maintenance record object as it now reads. A record of another possession or of another account, or a condition of another account, answers 404.',
            'response' => ['data' => $replating],
        ],
        [
            'id' => 'possession-maintenance-records-destroy',
            'title' => 'Delete a maintenance record',
            'method' => 'DELETE',
            'path' => '/vaults/{vault}/possessions/{possession}/maintenance-records/{maintenanceRecord}',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$dundieId}/maintenance-records/{$inspectionId}",
            'description' => 'Delete a maintenance record of a possession.',
            'body' => ['The deletion is final, and the maintenance history loses that job. When it was the latest record, the one before it becomes the latest, and its next due date counts again. The account log keeps the name of the possession.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam, $recordParam],
            'returns' => 'Nothing. A record of another possession or of another account answers 404.',
            'responseStatus' => 204,
            'response' => [],
        ],
    ],
];
