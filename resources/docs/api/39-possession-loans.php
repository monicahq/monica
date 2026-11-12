<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();
$application = rtrim((string) config('app.url'), '/');

$rateLimit = 'Sixty requests per minute, per user, shared by every key and device.';

$vaultSlug = 'dunder-mifflin';
$mugId = '8a3d1f62-7b4e-4c95-a0e1-2f6c9b5d3e71';
$weddingLoanId = '4c7e2a91-3f58-4b06-9d1e-6a2f8c3b5e04';
$harvestLoanId = 'b2e9f4c7-1a63-4d58-8e07-3c5d9a1f6b28';
$dwightId = '7f1c3e58-9a24-4b6d-8c03-5e2a7d9f1b46';
$pamId = 'd4a8c2f1-6e39-4b75-a0d2-8f1e3c7b5a69';
$goodId = '2e6b9d14-5c87-4a30-b9f2-1d4e8a6c3f57';

$webLink = 'The Loans section of the possession in the application, which opens for somebody signed in rather than for a key. It is the link to give a person.';

$weddingLoan = [
    'type' => 'possession_loan',
    'id' => $weddingLoanId,
    'attributes' => [
        'direction' => 'lent',
        'status' => 'returned',
        'is_overdue' => false,
        'contact_id' => $pamId,
        'contact_name' => 'Pam Beesly',
        'started_on' => '2024-06-12',
        'due_on' => '2024-06-15',
        'returned_on' => '2024-06-15',
        'condition_out_id' => $goodId,
        'condition_back_id' => $goodId,
        'deposit_amount' => null,
        'deposit_currency' => null,
        'purpose' => 'Worn at the wedding at Niagara Falls.',
        'is_in_provenance' => false,
        'created_at' => 1773446400,
    ],
    'links' => [
        'self' => "{$base}/vaults/{$vaultSlug}/possessions/{$mugId}/loans/{$weddingLoanId}",
        'web' => "{$application}/vaults/{$vaultSlug}/possessions/{$mugId}/loans",
    ],
];

$harvestLoan = [
    'type' => 'possession_loan',
    'id' => $harvestLoanId,
    'attributes' => [
        'direction' => 'lent',
        'status' => 'active',
        'is_overdue' => false,
        'contact_id' => $dwightId,
        'contact_name' => 'Dwight Schrute',
        'started_on' => '2026-11-01',
        'due_on' => '2026-11-30',
        'returned_on' => null,
        'condition_out_id' => $goodId,
        'condition_back_id' => null,
        'deposit_amount' => '50.00',
        'deposit_currency' => 'usd',
        'purpose' => 'The beet harvest party at Schrute Farms.',
        'is_in_provenance' => true,
        'created_at' => 1773450000,
    ],
    'links' => [
        'self' => "{$base}/vaults/{$vaultSlug}/possessions/{$mugId}/loans/{$harvestLoanId}",
        'web' => "{$application}/vaults/{$vaultSlug}/possessions/{$mugId}/loans",
    ],
];

$attributes = [
    ['name' => 'direction', 'type' => 'string', 'description' => 'lent when the possession left your hands, borrowed when it came into them.'],
    ['name' => 'status', 'type' => 'string', 'description' => 'planned, active or returned. A possession has at most one active loan.'],
    ['name' => 'is_overdue', 'type' => 'boolean', 'description' => 'Whether the loan is active and its due day has passed, in the time zone of the person asking. It is worked out, never stored.'],
    ['name' => 'contact_id', 'type' => 'uuid', 'description' => 'The identifier of the contact who has it or lent it, or null once that contact is deleted.'],
    ['name' => 'contact_name', 'type' => 'string', 'description' => 'The name of that contact, or null once that contact is deleted.'],
    ['name' => 'started_on', 'type' => 'date', 'description' => 'The day the loan started, as YYYY-MM-DD.'],
    ['name' => 'due_on', 'type' => 'date', 'description' => 'The day it is due back, as YYYY-MM-DD, or null when no day was agreed.'],
    ['name' => 'returned_on', 'type' => 'date', 'description' => 'The day it came back, as YYYY-MM-DD, or null unless the loan is returned.'],
    ['name' => 'condition_out_id', 'type' => 'uuid', 'description' => 'The identifier of the possession condition it left in, or null.'],
    ['name' => 'condition_back_id', 'type' => 'uuid', 'description' => 'The identifier of the possession condition it came back in, or null.'],
    ['name' => 'deposit_amount', 'type' => 'string', 'description' => 'A deposit left against the loan, as a decimal with two places, or null.'],
    ['name' => 'deposit_currency', 'type' => 'string', 'description' => 'The currency of the deposit, as a lowercase ISO code, or null without a deposit.'],
    ['name' => 'purpose', 'type' => 'string', 'description' => 'Why it was lent or borrowed, or null.'],
    ['name' => 'is_in_provenance', 'type' => 'boolean', 'description' => 'Whether the loan records a change of custody in the provenance when it starts and another when it ends.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the loan was recorded, as a count of seconds since 1970.'],
];

$vaultParam = ['name' => 'vault', 'type' => 'string', 'required' => true, 'description' => 'The vault the possession is kept in, named either by the readable name it reports as slug or by the identifier it reports as id. A vault of another account answers 404.'];
$possessionParam = ['name' => 'possession', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of the possession. A possession of another vault or of another account answers 404.'];
$loanParam = ['name' => 'loan', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of the loan. A loan of another possession answers 404.'];

$sharedBodyParams = [
    ['name' => 'due_on', 'type' => 'date', 'required' => false, 'description' => 'The day it is due back, as YYYY-MM-DD. Not before started_on.'],
    ['name' => 'returned_on', 'type' => 'date', 'required' => false, 'description' => 'The day it came back, as YYYY-MM-DD. Required for a returned loan, not before started_on, and ignored otherwise.'],
    ['name' => 'condition_out_id', 'type' => 'uuid', 'required' => false, 'description' => 'The identifier of a possession condition of the account, the one it left in.'],
    ['name' => 'condition_back_id', 'type' => 'uuid', 'required' => false, 'description' => 'The identifier of a possession condition of the account, the one it came back in. Kept only for a returned loan.'],
    ['name' => 'deposit_amount', 'type' => 'number', 'required' => false, 'description' => 'A deposit left against the loan, from 0 to 9,999,999,999.99.'],
    ['name' => 'deposit_currency', 'type' => 'string', 'required' => false, 'description' => 'The currency of the deposit, as a lowercase ISO code. Required with a deposit.'],
    ['name' => 'purpose', 'type' => 'string', 'required' => false, 'description' => 'Why it was lent or borrowed, up to 5,000 characters. Markup is stripped.'],
    ['name' => 'is_in_provenance', 'type' => 'boolean', 'required' => false, 'description' => 'Whether the loan records a change of custody in the provenance when it starts and another when it ends. False when left out.'],
];

return [
    'name' => 'Possession loans',
    'description' => 'Who has a possession and when it is due back. A loan is one period the possession is lent to a contact of its vault, or borrowed from one, without any change of owner, so it records no transaction. A possession has at most one active loan.',
    'sections' => [
        [
            'id' => 'possession-loans-index',
            'returnFields' => [
                ...ApiDocsFields::all('possession_loan', 'possession loan', 'possession loans', $attributes),
                ['name' => 'data[].links.web', 'type' => 'string', 'description' => $webLink],
            ],
            'title' => 'List the loans of a possession',
            'label' => 'List loans',
            'method' => 'GET',
            'path' => '/vaults/{vault}/possessions/{possession}/loans',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$mugId}/loans",
            'description' => 'List every loan of a possession, newest first.',
            'body' => ['On the same day, the latest recorded comes first. The list is whole rather than paginated.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam],
            'returns' => 'Every loan object of the possession, or an empty list. A vault or a possession of another account answers 404.',
            'response' => ['data' => [$harvestLoan, $weddingLoan]],
        ],
        [
            'id' => 'possession-loans-show',
            'returnFields' => ApiDocsFields::one('possession_loan', 'possession loan', $attributes, webLink: $webLink),
            'title' => 'Get a loan',
            'method' => 'GET',
            'path' => '/vaults/{vault}/possessions/{possession}/loans/{loan}',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$mugId}/loans/{$harvestLoanId}",
            'description' => 'Retrieve one loan of a possession.',
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam, $loanParam],
            'returns' => 'A loan object. A vault, a possession or a loan of another account answers 404.',
            'response' => ['data' => $harvestLoan],
        ],
        [
            'id' => 'possession-loans-create',
            'returnFields' => ApiDocsFields::one('possession_loan', 'possession loan', $attributes, webLink: $webLink),
            'title' => 'Record a loan',
            'method' => 'POST',
            'path' => '/vaults/{vault}/possessions/{possession}/loans',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$mugId}/loans",
            'description' => 'Record a possession lent to a contact of its vault, or borrowed from one.',
            'body' => [
                'Name an existing contact with contact_id, or write a new one down with new_contact: the contact and the loan are then saved together, or neither is. A contact of another vault answers 404. The change is written to the account log.',
                'A second active loan answers 422 on status ("Another loan of this possession is active. Mark it as returned first."). A due day before the start answers 422 on due_on, and a returned loan without its return day answers 422 on returned_on ("Say when it came back.").',
            ],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam],
            'bodyParams' => [
                ['name' => 'direction', 'type' => 'string', 'required' => true, 'description' => 'lent or borrowed.', 'example' => 'lent'],
                ['name' => 'status', 'type' => 'string', 'required' => true, 'description' => 'planned, active or returned.', 'example' => 'active'],
                ['name' => 'contact_id', 'type' => 'uuid', 'required' => false, 'description' => 'The identifier of a contact of the vault. Required without new_contact.', 'example' => $dwightId],
                ['name' => 'new_contact', 'type' => 'object', 'required' => false, 'description' => 'A contact to write down with the loan, as first_name (required) and last_name. Required without contact_id, and refused with it.'],
                ['name' => 'started_on', 'type' => 'date', 'required' => true, 'description' => 'The day the loan started, as YYYY-MM-DD.', 'example' => '2026-11-01'],
                ...$sharedBodyParams,
            ],
            'exampleBody' => ['direction' => 'lent', 'status' => 'active', 'contact_id' => $dwightId, 'started_on' => '2026-11-01', 'due_on' => '2026-11-30', 'deposit_amount' => 50, 'deposit_currency' => 'usd', 'is_in_provenance' => true],
            'returns' => 'The new loan object.',
            'responseStatus' => 201,
            'response' => ['data' => $harvestLoan],
        ],
        [
            'id' => 'possession-loans-update',
            'returnFields' => ApiDocsFields::one('possession_loan', 'possession loan', $attributes, webLink: $webLink),
            'title' => 'Change a loan',
            'method' => 'PUT',
            'path' => '/vaults/{vault}/possessions/{possession}/loans/{loan}',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$mugId}/loans/{$weddingLoanId}",
            'description' => 'Change one loan of a possession, including marking it as returned.',
            'body' => [
                'What you leave out keeps its value, and what you send as null is cleared. A loan that is not returned loses its return day and the condition it came back in, and its provenance events follow what it now says. The change is written to the account log.',
                'The same refusals apply as when recording a loan.',
            ],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam, $loanParam],
            'bodyParams' => [
                ['name' => 'direction', 'type' => 'string', 'required' => false, 'description' => 'lent or borrowed.'],
                ['name' => 'status', 'type' => 'string', 'required' => false, 'description' => 'planned, active or returned.'],
                ['name' => 'contact_id', 'type' => 'uuid', 'required' => false, 'description' => 'The identifier of a contact of the vault.'],
                ['name' => 'started_on', 'type' => 'date', 'required' => false, 'description' => 'The day the loan started, as YYYY-MM-DD.'],
                ...$sharedBodyParams,
            ],
            'exampleBody' => ['status' => 'returned', 'returned_on' => '2024-06-15', 'condition_back_id' => $goodId],
            'returns' => 'The loan object, as it now stands. A loan of another possession or of another account answers 404.',
            'response' => ['data' => $weddingLoan],
        ],
        [
            'id' => 'possession-loans-destroy',
            'title' => 'Delete a loan',
            'method' => 'DELETE',
            'path' => '/vaults/{vault}/possessions/{possession}/loans/{loan}',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$mugId}/loans/{$weddingLoanId}",
            'description' => 'Delete one loan of a possession.',
            'body' => ['The deletion is final, and the provenance events the loan wrote are deleted with it. The contact stays. The account log keeps the name of the possession.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam, $loanParam],
            'returns' => 'Nothing. A loan of another possession or of another account answers 404.',
            'responseStatus' => 204,
            'response' => [],
        ],
    ],
];
