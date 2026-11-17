<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();
$application = rtrim((string) config('app.url'), '/');

$rateLimit = 'Sixty requests per minute, per user, shared by every key and device.';

$vaultSlug = 'dunder-mifflin';
$dundieId = '9c4e2a71-5b38-4f06-8d1e-2a7f3c6b9e15';
$purchaseId = '3f8a1c62-7d94-4b2e-a5c0-6e9b2d4f1a83';
$feeId = 'b71d5e09-2c46-4a8f-9e3b-0f5c7a2d6e14';
$documentId = '5c2e8f14-9a63-4d7b-b0e1-3f6a9c4d2e58';

$webLink = 'The Transactions section of the possession in the application, which opens for somebody signed in rather than for a key. It is the link to give a person.';

$purchase = [
    'type' => 'possession_transaction',
    'id' => $purchaseId,
    'attributes' => [
        'type' => 'purchase',
        'changes_ownership' => true,
        'is_acquisition' => true,
        'occurred_on' => '2005-03-14',
        'counterparty' => 'Dundies Inc.',
        'amount' => '40.00',
        'tax' => '3.20',
        'fees' => null,
        'shipping' => '6.00',
        'entered_total' => null,
        'total' => '49.20',
        'total_source' => 'sum',
        'currency' => 'usd',
        'reference' => 'Invoice 4021',
        'source_url' => null,
        'note' => 'For Kevin, the hottest in the office award.',
        'documents' => [['id' => $documentId, 'name' => 'Invoice 4021.pdf', 'mime_type' => 'application/pdf', 'size_in_bytes' => 88000]],
        'created_at' => 1761436800,
        'updated_at' => 1761436800,
    ],
    'links' => [
        'self' => "{$base}/vaults/{$vaultSlug}/possessions/{$dundieId}/transactions/{$purchaseId}",
        'web' => "{$application}/vaults/{$vaultSlug}/possessions/{$dundieId}/transactions",
    ],
];

$fee = [
    'type' => 'possession_transaction',
    'id' => $feeId,
    'attributes' => [
        'type' => 'fee',
        'changes_ownership' => false,
        'is_acquisition' => false,
        'occurred_on' => '2006-01-03',
        'counterparty' => 'Chili’s',
        'amount' => null,
        'tax' => null,
        'fees' => '85.00',
        'shipping' => null,
        'entered_total' => null,
        'total' => '85.00',
        'total_source' => 'sum',
        'currency' => 'usd',
        'reference' => null,
        'source_url' => null,
        'note' => null,
        'documents' => [],
        'created_at' => 1761440400,
        'updated_at' => 1761440400,
    ],
    'links' => [
        'self' => "{$base}/vaults/{$vaultSlug}/possessions/{$dundieId}/transactions/{$feeId}",
        'web' => "{$application}/vaults/{$vaultSlug}/possessions/{$dundieId}/transactions",
    ],
];

$attributes = [
    ['name' => 'type', 'type' => 'string', 'description' => 'What happened: purchase, sale, exchange, gift_received, gift_given, inheritance, refund, fee, tax, shipping or other.'],
    ['name' => 'changes_ownership', 'type' => 'boolean', 'description' => 'Whether the possession changed hands, true for the first six types and false for the money only ones.'],
    ['name' => 'is_acquisition', 'type' => 'boolean', 'description' => 'Whether this is the transaction that brought the possession in: its earliest purchase, exchange, gift received or inheritance, undated ones first.'],
    ['name' => 'occurred_on', 'type' => 'string', 'description' => 'The day it happened, written as 2005-03-14, or null when it is not known.'],
    ['name' => 'counterparty', 'type' => 'string', 'description' => 'Who was on the other side, as text, or null.'],
    ['name' => 'amount', 'type' => 'string', 'description' => 'The price or the amount, as a string with two decimals, or null.'],
    ['name' => 'tax', 'type' => 'string', 'description' => 'The tax paid, or null.'],
    ['name' => 'fees', 'type' => 'string', 'description' => 'The fees paid, or null.'],
    ['name' => 'shipping', 'type' => 'string', 'description' => 'The shipping paid, or null.'],
    ['name' => 'entered_total', 'type' => 'string', 'description' => 'The total typed in, which overrides the sum of the four figures, or null.'],
    ['name' => 'total', 'type' => 'string', 'description' => 'The total of the transaction: the entered total, else the sum of the figures set, else null.'],
    ['name' => 'total_source', 'type' => 'string', 'description' => 'Where the total comes from: entered, sum or unknown.'],
    ['name' => 'currency', 'type' => 'string', 'description' => 'The currency of every figure, in lower case, or null when no figure is set. Nothing is ever converted.'],
    ['name' => 'reference', 'type' => 'string', 'description' => 'An invoice, receipt or deed number, or null.'],
    ['name' => 'source_url', 'type' => 'string', 'description' => 'Where the transaction can be checked, or null.'],
    ['name' => 'note', 'type' => 'string', 'description' => 'Anything worth remembering about it, or null.'],
    ['name' => 'documents', 'type' => 'array', 'description' => 'The invoices, receipts and deeds attached to it, oldest first.'],
    ['name' => 'documents[]', 'type' => 'object', 'description' => 'One document.'],
    ['name' => 'documents[].id', 'type' => 'uuid', 'description' => 'The id of the file, which is what removing the document takes.'],
    ['name' => 'documents[].name', 'type' => 'string', 'description' => 'The name of the file as it was uploaded.'],
    ['name' => 'documents[].mime_type', 'type' => 'string', 'description' => 'The kind of the file, such as application/pdf.'],
    ['name' => 'documents[].size_in_bytes', 'type' => 'integer', 'description' => 'How much storage the file takes.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the transaction was recorded, as a count of seconds since 1970.'],
    ['name' => 'updated_at', 'type' => 'timestamp', 'description' => 'When it last changed, as a count of seconds, or null.'],
];

$attributesWhenRecorded = array_values(array_filter($attributes, fn (array $field): bool => ! str_starts_with($field['name'], 'documents[]')));

$recorded = ['data' => [...$purchase, 'attributes' => [...$purchase['attributes'], 'documents' => []]]];

$vaultParam = ['name' => 'vault', 'type' => 'string', 'required' => true, 'description' => 'The vault the possession is kept in, named either by the readable name it reports as slug or by its id. A vault of another account answers 404.'];
$possessionParam = ['name' => 'possession', 'type' => 'uuid', 'required' => true, 'description' => 'The id of the possession. A possession of another vault or of another account answers 404.'];
$transactionParam = ['name' => 'transaction', 'type' => 'uuid', 'required' => true, 'description' => 'The id of the transaction. A transaction of another possession answers 404.'];

$figure = 'Zero or more, up to 9999999999.99. A figure below zero is refused with “Write an amount of zero or more.”';

return [
    'name' => 'Possession transactions',
    'description' => 'Money and ownership changing hands for a possession. Its acquisition date, its acquisition price and what it cost since are read from here.',
    'sections' => [
        [
            'id' => 'possession-transactions-index',
            'returnFields' => [
                ...ApiDocsFields::all('possession_transaction', 'possession transaction', 'possession transactions', $attributes),
                ['name' => 'data[].links.web', 'type' => 'string', 'description' => $webLink],
            ],
            'title' => 'List the transactions of a possession',
            'label' => 'List transactions',
            'method' => 'GET',
            'path' => '/vaults/{vault}/possessions/{possession}/transactions',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$dundieId}/transactions",
            'description' => 'List every transaction of a possession, oldest first.',
            'body' => ['An undated transaction counts as the oldest and comes first; two on the same day come in the order they were recorded. The list is whole rather than paginated.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam],
            'returns' => 'Every transaction object of the possession, or an empty list. A vault or a possession of another account answers 404.',
            'response' => ['data' => [$purchase, $fee]],
        ],
        [
            'id' => 'possession-transactions-show',
            'returnFields' => ApiDocsFields::one('possession_transaction', 'possession transaction', $attributes, webLink: $webLink),
            'title' => 'Get a transaction',
            'method' => 'GET',
            'path' => '/vaults/{vault}/possessions/{possession}/transactions/{transaction}',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$dundieId}/transactions/{$purchaseId}",
            'description' => 'Retrieve one transaction of a possession.',
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam, $transactionParam],
            'returns' => 'A transaction object. A vault, a possession or a transaction of another account answers 404.',
            'response' => ['data' => $purchase],
        ],
        [
            'id' => 'possession-transactions-create',
            'returnFields' => ApiDocsFields::one('possession_transaction', 'possession transaction', $attributesWhenRecorded, webLink: $webLink),
            'title' => 'Record a transaction',
            'method' => 'POST',
            'path' => '/vaults/{vault}/possessions/{possession}/transactions',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$dundieId}/transactions",
            'description' => 'Record money or ownership changing hands for a possession.',
            'body' => [
                'Only the type is required. The earliest purchase, exchange, gift received or inheritance becomes the acquisition of the possession, so recording an earlier one moves it.',
                'The change is written to the account log.',
            ],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam],
            'bodyParams' => [
                ['name' => 'type', 'type' => 'string', 'required' => true, 'description' => 'What happened: purchase, sale, exchange, gift_received, gift_given, inheritance, refund, fee, tax, shipping or other.', 'example' => 'purchase'],
                ['name' => 'occurred_on', 'type' => 'string', 'required' => false, 'description' => 'The day it happened, written as 2005-03-14. Left out when it is not known.', 'example' => '2005-03-14'],
                ['name' => 'counterparty', 'type' => 'string', 'required' => false, 'description' => 'Who was on the other side, as text, up to 255 characters. Markup is stripped.', 'example' => 'Dundies Inc.'],
                ['name' => 'amount', 'type' => 'number', 'required' => false, 'description' => "The price or the amount. {$figure}", 'example' => 40],
                ['name' => 'tax', 'type' => 'number', 'required' => false, 'description' => "The tax paid. {$figure}", 'example' => 3.2],
                ['name' => 'fees', 'type' => 'number', 'required' => false, 'description' => "The fees paid. {$figure}"],
                ['name' => 'shipping', 'type' => 'number', 'required' => false, 'description' => "The shipping paid. {$figure}", 'example' => 6],
                ['name' => 'entered_total', 'type' => 'number', 'required' => false, 'description' => "The total, when it is not the sum of the four figures. {$figure}"],
                ['name' => 'currency', 'type' => 'string', 'required' => false, 'description' => 'The currency of every figure: usd, eur, gbp, cad, aud, chf, jpy, brl, inr or cny. Required once a figure is set, else refused with “Choose the currency of these amounts.”', 'example' => 'usd'],
                ['name' => 'reference', 'type' => 'string', 'required' => false, 'description' => 'An invoice, receipt or deed number, up to 255 characters.', 'example' => 'Invoice 4021'],
                ['name' => 'source_url', 'type' => 'string', 'required' => false, 'description' => 'A full web address where the transaction can be checked, up to 2,048 characters.'],
                ['name' => 'note', 'type' => 'string', 'required' => false, 'description' => 'Anything worth remembering about it, up to 5,000 characters.', 'example' => 'For Kevin, the hottest in the office award.'],
            ],
            'returns' => 'The transaction object that was recorded.',
            'responseStatus' => 201,
            'response' => $recorded,
        ],
        [
            'id' => 'possession-transactions-update',
            'returnFields' => ApiDocsFields::one('possession_transaction', 'possession transaction', $attributes, webLink: $webLink),
            'title' => 'Change a transaction',
            'method' => 'PUT',
            'path' => '/vaults/{vault}/possessions/{possession}/transactions/{transaction}',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$dundieId}/transactions/{$purchaseId}",
            'description' => 'Change what a transaction says, under the rules that recorded it.',
            'body' => [
                'A field left out of the request keeps its value, and a field sent as null is cleared. The acquisition date and price of the possession follow, since they are read from its transactions.',
                'The change is written to the account log.',
            ],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam, $transactionParam],
            'bodyParams' => [
                ['name' => 'type', 'type' => 'string', 'required' => false, 'description' => 'What happened, one of the eleven types.'],
                ['name' => 'occurred_on', 'type' => 'string', 'required' => false, 'description' => 'The day it happened, written as 2005-03-14, or null when it is not known.'],
                ['name' => 'counterparty', 'type' => 'string', 'required' => false, 'description' => 'Who was on the other side, as text, up to 255 characters.'],
                ['name' => 'amount', 'type' => 'number', 'required' => false, 'description' => "The price or the amount. {$figure}"],
                ['name' => 'tax', 'type' => 'number', 'required' => false, 'description' => "The tax paid. {$figure}"],
                ['name' => 'fees', 'type' => 'number', 'required' => false, 'description' => "The fees paid. {$figure}"],
                ['name' => 'shipping', 'type' => 'number', 'required' => false, 'description' => "The shipping paid. {$figure}", 'example' => 6],
                ['name' => 'entered_total', 'type' => 'number', 'required' => false, 'description' => "The total, when it is not the sum of the four figures. {$figure}"],
                ['name' => 'currency', 'type' => 'string', 'required' => false, 'description' => 'The currency of every figure. Required once a figure is set, else refused with “Choose the currency of these amounts.”'],
                ['name' => 'reference', 'type' => 'string', 'required' => false, 'description' => 'An invoice, receipt or deed number, up to 255 characters.'],
                ['name' => 'source_url', 'type' => 'string', 'required' => false, 'description' => 'A full web address where the transaction can be checked.'],
                ['name' => 'note', 'type' => 'string', 'required' => false, 'description' => 'Anything worth remembering about it.'],
            ],
            'returns' => 'The transaction object as it now reads. A transaction of another possession or of another account answers 404.',
            'response' => ['data' => $purchase],
        ],
        [
            'id' => 'possession-transactions-destroy',
            'title' => 'Delete a transaction',
            'method' => 'DELETE',
            'path' => '/vaults/{vault}/possessions/{possession}/transactions/{transaction}',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$dundieId}/transactions/{$feeId}",
            'description' => 'Delete a transaction of a possession, with the documents attached to it.',
            'body' => ['The deletion is final. When it was the acquisition, the next earliest purchase, exchange, gift received or inheritance becomes the acquisition. The account log keeps the name of the possession.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam, $transactionParam],
            'returns' => 'Nothing. A transaction of another possession or of another account answers 404.',
            'responseStatus' => 204,
            'response' => [],
        ],
        [
            'id' => 'possession-transaction-documents-create',
            'flag' => ['file-upload'],
            'returnFields' => ApiDocsFields::one('possession_transaction', 'possession transaction', $attributes, webLink: $webLink),
            'title' => 'Attach a document to a transaction',
            'label' => 'Attach a document',
            'method' => 'POST',
            'path' => '/vaults/{vault}/possessions/{possession}/transactions/{transaction}/documents',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$dundieId}/transactions/{$purchaseId}/documents",
            'description' => 'Upload an invoice, a receipt or a deed and attach it to a transaction. The change is written to the account log.',
            'body' => [
                'This endpoint exists only while file uploads are switched on for the account. Otherwise it answers 404.',
                'Send the file as multipart form data, in a field called file. The type of the file is not restricted, and it counts against the storage of the account.',
                'An upload larger than the size this instance allows answers 422 on file, and so does an upload that would take the account past the storage it has left. In both cases nothing is kept.',
            ],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam, $transactionParam],
            'bodyParams' => [
                ['name' => 'file', 'type' => 'file', 'required' => true, 'description' => 'The document to attach. A request without one answers 422 on file.'],
            ],
            'returns' => 'The transaction object, with the document in its documents. A transaction of another possession or of another account answers 404.',
            'responseStatus' => 201,
            'response' => ['data' => $purchase],
        ],
        [
            'id' => 'possession-transaction-documents-destroy',
            'title' => 'Remove a document from a transaction',
            'label' => 'Remove a document',
            'method' => 'DELETE',
            'path' => '/vaults/{vault}/possessions/{possession}/transactions/{transaction}/documents/{file}',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$dundieId}/transactions/{$purchaseId}/documents/{$documentId}",
            'description' => 'Remove a document from a transaction. The change is written to the account log.',
            'body' => ['The file is let go rather than deleted outright. It stays wherever else it is used, and is swept up after a grace period once nothing uses it.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [
                $vaultParam,
                $possessionParam,
                $transactionParam,
                ['name' => 'file', 'type' => 'uuid', 'required' => true, 'description' => 'The id of the document, as the documents of the transaction give it. A document of another transaction answers 404.'],
            ],
            'returns' => 'Nothing. A transaction of another possession, a document of another transaction or of another account answers 404.',
            'responseStatus' => 204,
            'response' => [],
        ],
    ],
];
