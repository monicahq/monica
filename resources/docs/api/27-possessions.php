<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();

$rateLimit = 'Sixty requests per minute, per user, shared by every key and device.';

$vaultSlug = 'dunder-mifflin';
$mugId = '6e2b9d41-8c37-4f15-a0d6-3b7e1c9f2a84';
$sebringId = 'c4f81a27-5d93-4e60-b2a8-9e1d7c3b5f06';
$officeEquipmentId = '9c1d4e7a-3b52-4f86-a0d9-2e7b6c418f35';
$vehicleId = '3a7f5c19-2e84-4d6b-9f03-8c1b6e4a2d57';
$scrantonId = '5b7e2c90-4d18-4a3f-9e61-0c8d2f7a1b43';
$parkingLotId = 'f19c3e6a-7b42-4d85-a0e7-2c6d9b1f8a34';
$paymentId = 'b7e04a93-1c6f-4d28-9a35-6f8e2d1c4b90';
$conditionId = 'e5c37a28-9f14-4b6d-a083-7d2e1f9b6c45';
$serialId = '2d8b5f1e-6a47-4c93-8e02-1b9f7c3a5d64';
$giftId = '8f3a6c21-4e97-4b05-9d18-7a2c5e0f9b36';
$insuredId = '1e6b9d47-3c28-4f71-a5e0-9d4c2b8f6a13';

$mug = [
    'type' => 'possession',
    'id' => $mugId,
    'attributes' => [
        'name' => 'World’s Best Boss mug',
        'description' => 'Bought by Michael for Michael, from Spencer Gifts.',
        'identifier' => 'WBB-0001',
        'status' => 'owned',
        'quantity' => 1,
        'is_featured' => true,
        'position' => 1,
        'possession_type' => [
            'id' => $officeEquipmentId,
            'name' => 'Office equipment',
            'colour' => '#2f7f86',
            'icon' => 'briefcase',
        ],
        'location' => [
            'id' => $scrantonId,
            'name' => 'Dunder Mifflin Scranton',
        ],
        'values' => [
            ['field_id' => $paymentId, 'name' => 'Payment method', 'kind' => 'select', 'group' => 'Purchase', 'value' => 'Card'],
            ['field_id' => $conditionId, 'name' => 'Condition', 'kind' => 'select', 'group' => null, 'value' => 'Used'],
            ['field_id' => $serialId, 'name' => 'Serial number', 'kind' => 'text', 'group' => null, 'value' => 'SG-2005-0324'],
        ],
        'acquisition' => [
            'way' => 'gift_received',
            'acquired_from' => ['name' => 'Jim Halpert'],
            'acquired_on' => '2005-03-24',
            'transaction_id' => $giftId,
            'declared_value' => ['amount' => '12.99', 'currency' => 'usd'],
        ],
        'current_value' => ['amount' => '15.00', 'currency' => 'usd', 'source' => 'valuation'],
        'tags' => [
            ['id' => $insuredId, 'name' => 'insured', 'colour' => '#3f5fd0'],
        ],
        'created_at' => 1761523200,
        'updated_at' => 1761526800,
    ],
    'links' => [
        'self' => "{$base}/vaults/{$vaultSlug}/possessions/{$mugId}",
    ],
];

$sebring = [
    'type' => 'possession',
    'id' => $sebringId,
    'attributes' => [
        'name' => 'Chrysler Sebring',
        'description' => 'Bought with the first commission check. Drove into a lake once, following the GPS.',
        'identifier' => null,
        'status' => 'owned',
        'quantity' => 1,
        'is_featured' => false,
        'position' => null,
        'possession_type' => [
            'id' => $vehicleId,
            'name' => 'Vehicle',
            'colour' => '#3f5fd0',
            'icon' => 'car',
        ],
        'location' => [
            'id' => $parkingLotId,
            'name' => 'Scranton parking lot',
        ],
        'values' => [],
        'acquisition' => [
            'way' => null,
            'acquired_from' => null,
            'acquired_on' => null,
            'transaction_id' => null,
            'declared_value' => null,
        ],
        'current_value' => null,
        'tags' => [],
        'created_at' => 1761526800,
        'updated_at' => 1761526800,
    ],
    'links' => [
        'self' => "{$base}/vaults/{$vaultSlug}/possessions/{$sebringId}",
    ],
];

$possessionAttributes = [
    ['name' => 'name', 'type' => 'string', 'description' => 'What the thing is called, such as World’s Best Boss mug.'],
    ['name' => 'description', 'type' => 'string', 'description' => 'Free text about the thing, or null.'],
    ['name' => 'identifier', 'type' => 'string', 'description' => 'A serial number, a licence plate or any other reference written on the thing, or null.'],
    ['name' => 'status', 'type' => 'string', 'description' => 'Where the thing stands: owned, ordered, sold, given_away, lost, stolen or thrown_away.'],
    ['name' => 'quantity', 'type' => 'integer', 'description' => 'How many of the thing there are, at least 1.'],
    ['name' => 'is_featured', 'type' => 'boolean', 'description' => 'Whether the thing is featured, which places it in the order shown first in the vault.'],
    ['name' => 'position', 'type' => 'integer', 'description' => 'Its place among the featured possessions of the vault, counting from 1, or null when it is not featured.'],
    ['name' => 'possession_type', 'type' => 'object', 'description' => 'The kind of thing it is. It is chosen when the possession is added and never changes.'],
    ['name' => 'possession_type.id', 'type' => 'uuid', 'description' => 'The id of the possession type.'],
    ['name' => 'possession_type.name', 'type' => 'string', 'description' => 'What the type is called.'],
    ['name' => 'possession_type.colour', 'type' => 'string', 'description' => 'The colour of the type, as a hexadecimal value.'],
    ['name' => 'possession_type.icon', 'type' => 'string', 'description' => 'The icon of the type, one of the glyphs of the catalogue, or null.'],
    ['name' => 'location', 'type' => 'object', 'description' => 'Where the thing is kept now, or null when nobody said.'],
    ['name' => 'location.id', 'type' => 'uuid', 'description' => 'The id of the location.'],
    ['name' => 'location.name', 'type' => 'string', 'description' => 'What the location is called.'],
    ['name' => 'values', 'type' => 'array', 'description' => 'What the thing holds for the fields of its type, in the order of the fields: the groups first, then the standalone fields. A field holding nothing is left out.'],
    ['name' => 'values[]', 'type' => 'object', 'description' => 'One value.'],
    ['name' => 'values[].field_id', 'type' => 'uuid', 'description' => 'The id of the field, which is the key to send when writing its value.'],
    ['name' => 'values[].name', 'type' => 'string', 'description' => 'What the field is called.'],
    ['name' => 'values[].kind', 'type' => 'string', 'description' => 'What the field holds: text, number, date, boolean, select or rating.'],
    ['name' => 'values[].group', 'type' => 'string', 'description' => 'The name of the group the field sits in, or null when it stands alone.'],
    ['name' => 'values[].value', 'type' => 'string', 'description' => 'The value, as the kind of the field reads it: a string for text, a date written as 2005-03-24 and a choice, a number for a number, an integer from 1 to 5 for a rating, and true or false for a boolean.'],
    ['name' => 'acquisition', 'type' => 'object', 'description' => 'How the thing was acquired, read from its acquisition transaction: the earliest purchase, exchange, gift received or inheritance. Each part is null without one. Change it through the transactions, since none of it can be written here.'],
    ['name' => 'acquisition.way', 'type' => 'string', 'description' => 'The type of the acquisition transaction: purchase, exchange, gift_received or inheritance, or null.'],
    ['name' => 'acquisition.acquired_from', 'type' => 'object', 'description' => 'Who it came from, or null when the transaction names nobody.'],
    ['name' => 'acquisition.acquired_from.name', 'type' => 'string', 'description' => 'The counterparty of the acquisition transaction, as text.'],
    ['name' => 'acquisition.acquired_on', 'type' => 'string', 'description' => 'The day of the acquisition transaction, written as 2005-03-24, or null.'],
    ['name' => 'acquisition.transaction_id', 'type' => 'uuid', 'description' => 'The id of the acquisition transaction, or null.'],
    ['name' => 'acquisition.declared_value', 'type' => 'object', 'description' => 'What it would cost to replace today, or null.'],
    ['name' => 'acquisition.declared_value.amount', 'type' => 'string', 'description' => 'The amount, a decimal with two digits after the point, such as 12.99.'],
    ['name' => 'acquisition.declared_value.currency', 'type' => 'string', 'description' => 'The currency: usd, eur, gbp, cad, aud, chf, jpy, brl, inr or cny.'],
    ['name' => 'current_value', 'type' => 'object', 'description' => 'What the thing is worth now: its latest valuation, else its declared value, else null. Read from its valuations, so it cannot be written here.'],
    ['name' => 'current_value.amount', 'type' => 'string', 'description' => 'The amount, a decimal with two digits after the point, such as 15.00.'],
    ['name' => 'current_value.currency', 'type' => 'string', 'description' => 'The currency of the amount, in lower case. Nothing is ever converted.'],
    ['name' => 'current_value.source', 'type' => 'string', 'description' => 'Where the amount comes from: valuation or declared_value.'],
    ['name' => 'tags', 'type' => 'array', 'description' => 'The tags the thing carries, sorted by name.'],
    ['name' => 'tags[]', 'type' => 'object', 'description' => 'One tag.'],
    ['name' => 'tags[].id', 'type' => 'uuid', 'description' => 'The id of the tag.'],
    ['name' => 'tags[].name', 'type' => 'string', 'description' => 'What the tag reads.'],
    ['name' => 'tags[].colour', 'type' => 'string', 'description' => 'The colour of the tag, as a hexadecimal value.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the possession was added, as a count of seconds since 1970.'],
    ['name' => 'updated_at', 'type' => 'timestamp', 'description' => 'When it last changed, as a count of seconds.'],
];

$one = ApiDocsFields::one('possession', 'possession', $possessionAttributes);

$newPossessionAttributes = array_map(
    fn (array $attribute): array => $attribute['name'] === 'values'
        ? [...$attribute, 'description' => 'What the thing holds for the fields of its type, an empty list for a new possession.']
        : $attribute,
    array_values(array_filter($possessionAttributes, fn (array $attribute): bool => ! str_starts_with($attribute['name'], 'values[') && $attribute['name'] !== 'acquisition.acquired_from.name')),
);

$vaultParam = ['name' => 'vault', 'type' => 'string', 'required' => true, 'description' => 'The vault the possession is kept in, named either by the readable name it reports as slug or by its id. A vault of another account answers 404.'];
$possessionParam = ['name' => 'possession', 'type' => 'uuid', 'required' => true, 'description' => 'The id of the possession. A possession of another vault or of another account answers 404.'];

$attributeBody = [
    ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => 'What the thing is called. Up to 255 characters. Markup and surrounding blank space are stripped, and a name left with nothing in it is refused with “Write a name for this possession.”', 'example' => 'World’s Best Boss mug'],
    ['name' => 'description', 'type' => 'string', 'required' => false, 'description' => 'Free text about the thing, up to 2,000 characters. Null or blank, there is none.', 'example' => 'Bought by Michael for Michael, from Spencer Gifts.'],
    ['name' => 'location_id', 'type' => 'uuid', 'required' => false, 'description' => 'The location the thing is kept in. Null, there is none. A location of another account is refused.', 'example' => $scrantonId],
    ['name' => 'identifier', 'type' => 'string', 'required' => false, 'description' => 'A serial number, a licence plate or any other reference, up to 255 characters. Null or blank, there is none.', 'example' => 'WBB-0001'],
    ['name' => 'status', 'type' => 'string', 'required' => false, 'description' => 'One of owned, ordered, sold, given_away, lost, stolen or thrown_away.', 'default' => 'owned', 'example' => 'owned'],
    ['name' => 'quantity', 'type' => 'integer', 'required' => false, 'description' => 'How many there are, between 1 and 1,000,000.', 'default' => '1', 'example' => 1],
    ['name' => 'is_featured', 'type' => 'boolean', 'required' => false, 'description' => 'Whether the thing is featured. A possession becoming featured is placed last among the featured possessions of the vault.', 'default' => 'false', 'example' => true],
    ['name' => 'declared_value', 'type' => 'number', 'required' => false, 'description' => 'What it would cost to replace today, from 0 to 9999999999.99, kept with two decimals.', 'example' => 12.99],
    ['name' => 'declared_value_currency', 'type' => 'string', 'required' => false, 'description' => 'The currency of the declared value: usd, eur, gbp, cad, aud, chf, jpy, brl, inr or cny. Required with a value, and dropped without one.', 'example' => 'usd'],
    ['name' => 'tags', 'type' => 'array', 'required' => false, 'description' => 'The names of the tags the thing carries, each up to 50 characters. A name matching a tag of the account, ignoring case, uses that tag; any other name creates a tag in the account.', 'example' => ['insured']],
];

return [
    'name' => 'Possessions',
    'description' => 'The things kept in a vault: what they are, where they are, and what the fields of their type say about them.',
    'sections' => [
        [
            'id' => 'possessions-index',
            'returnFields' => ApiDocsFields::page('possession', 'possession', 'possessions', $possessionAttributes),
            'title' => 'List possessions',
            'method' => 'GET',
            'path' => '/vaults/{vault}/possessions',
            'examplePath' => "/vaults/{$vaultSlug}/possessions",
            'description' => 'List the possessions of a vault, sorted by name, each with its type, its location and its values.',
            'body' => [
                'A possession belongs to a vault. Its type and its location belong to the account, so the same type and the same location serve every vault.',
                'Sorted by position, the featured possessions come first in their order, followed by every other possession sorted by name.',
                'To go through everything a vault holds, ask for compact possessions with the largest page.',
            ],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam],
            'queryParams' => [
                ['name' => 'type', 'type' => 'uuid', 'required' => false, 'description' => 'Only the possessions of this possession type.'],
                ['name' => 'status', 'type' => 'string', 'required' => false, 'description' => 'Only the possessions with this status: owned, ordered, sold, given_away, lost, stolen or thrown_away.'],
                ['name' => 'featured', 'type' => 'boolean', 'required' => false, 'description' => 'Only the featured possessions when true, only the others when false.'],
                ['name' => 'location', 'type' => 'uuid', 'required' => false, 'description' => 'Only the possessions kept at this location or at any location inside it. A location that is not one of the account answers 422.'],
                ['name' => 'sort', 'type' => 'string', 'required' => false, 'description' => 'Either name or position.', 'default' => 'name'],
                ['name' => 'compact', 'type' => 'boolean', 'required' => false, 'description' => 'When true, each possession carries only its name, its status, the id and name of its type, its location and its current value, which is about a tenth of the full record. Get one possession for the rest.', 'default' => 'false'],
                ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Which page to answer with.', 'default' => '1'],
                ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'How many possessions one page holds, between 1 and 100. A number outside the range is brought back into it.', 'default' => '25'],
            ],
            'returns' => 'A page of possession objects, and how many there are in all. A vault with none answers an empty list, and a vault of another account answers 404.',
            'response' => ['data' => [$mug, $sebring], 'meta' => ['page' => 1, 'per_page' => 25, 'total' => 2]],
        ],
        [
            'id' => 'possessions-show',
            'returnFields' => $one,
            'title' => 'Get a possession',
            'method' => 'GET',
            'path' => '/vaults/{vault}/possessions/{possession}',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$mugId}",
            'description' => 'Retrieve one possession with its type, its location and its values.',
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam],
            'returns' => 'A possession object. A vault or a possession of another account answers 404.',
            'response' => ['data' => $mug],
        ],
        [
            'id' => 'possessions-create',
            'returnFields' => ApiDocsFields::one('possession', 'possession', $newPossessionAttributes),
            'title' => 'Add a possession',
            'method' => 'POST',
            'path' => '/vaults/{vault}/possessions',
            'examplePath' => "/vaults/{$vaultSlug}/possessions",
            'description' => 'Record something kept in a vault. Write the values of its fields afterwards.',
            'body' => [
                'How it was acquired is not written here: record it as a transaction of the possession. Sending acquisition, acquired_from_id or acquired_on answers 422.',
                'Left out, a possession is owned, counts one and is not featured.',
                'A type or a location of another account answers 422 on type_id or location_id. The change is written to the account log.',
            ],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam],
            'bodyParams' => [
                ['name' => 'type_id', 'type' => 'uuid', 'required' => true, 'description' => 'The possession type of the thing. A type of another account is refused. The type cannot be changed later.', 'example' => $officeEquipmentId],
                ...$attributeBody,
            ],
            'returns' => 'The possession object that was added, holding no value yet.',
            'responseStatus' => 201,
            'response' => ['data' => [...$mug, 'attributes' => [...$mug['attributes'], 'values' => [], 'acquisition' => [...$mug['attributes']['acquisition'], 'way' => null, 'acquired_from' => null, 'acquired_on' => null, 'transaction_id' => null], 'current_value' => ['amount' => '12.99', 'currency' => 'usd', 'source' => 'declared_value'], 'updated_at' => 1761523200]]],
        ],
        [
            'id' => 'possessions-update',
            'returnFields' => $one,
            'title' => 'Change a possession',
            'method' => 'PUT',
            'path' => '/vaults/{vault}/possessions/{possession}',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$mugId}",
            'description' => 'Change what is known of a possession. Its type never changes, and its values are written separately.',
            'body' => [
                'How it was acquired is not written here: record it as a transaction of the possession. Sending acquisition, acquired_from_id or acquired_on answers 422.',
                'Send only what changes. The name, the status, the quantity and is_featured cannot be null. The description, the location, the identifier and the declared value are cleared by null, and tags sent as null or empty remove every tag. Tags sent replace the tags the thing carries.',
                'A possession becoming featured is placed last among the featured possessions of the vault. One that stops being featured leaves the order, and the others close the gap.',
                'A location of another account answers 422 on location_id. The change is written to the account log.',
            ],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam],
            'bodyParams' => array_map(fn (array $param): array => [...array_diff_key($param, ['default' => true]), 'required' => false], $attributeBody),
            'returns' => 'The possession object as it now reads. A vault or a possession of another account answers 404.',
            'response' => ['data' => $mug],
        ],
        [
            'id' => 'possessions-destroy',
            'title' => 'Delete a possession',
            'method' => 'DELETE',
            'path' => '/vaults/{vault}/possessions/{possession}',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$sebringId}",
            'description' => 'Delete a possession with the values it held.',
            'body' => [
                'The possession is deleted for good, with the values it held. A featured one leaves the order, and the others close the gap.',
                'The account log keeps the name of the possession.',
            ],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam],
            'returns' => 'Nothing. A vault or a possession of another account answers 404.',
            'responseStatus' => 204,
            'response' => [],
        ],
        [
            'id' => 'possessions-position',
            'returnFields' => $one,
            'title' => 'Move a featured possession',
            'method' => 'PUT',
            'path' => '/vaults/{vault}/possessions/{possession}/position',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$mugId}/position",
            'description' => 'Move a featured possession to another place among the featured possessions of its vault. Every one of them is numbered again from 1.',
            'body' => ['A possession that is not featured has no place in the order, and answers 422 on position with “Only a featured possession has a place in the order.”'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam],
            'bodyParams' => [
                ['name' => 'position', 'type' => 'integer', 'required' => true, 'description' => 'Where it goes, counting from 1. A number past the end puts it last.', 'example' => 1],
            ],
            'returns' => 'The possession object as it now reads. A vault or a possession of another account answers 404.',
            'response' => ['data' => $mug],
        ],
        [
            'id' => 'possessions-values',
            'returnFields' => $one,
            'title' => 'Write the values of a possession',
            'method' => 'PUT',
            'path' => '/vaults/{vault}/possessions/{possession}/values',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$mugId}/values",
            'description' => 'Write what a possession holds for some fields of its type. Only the fields named change.',
            'body' => [
                'A value of null or "" removes what the field held. Every value is checked against its field first, and nothing is written unless every one of them fits.',
                'Each value that does not fit answers 422 on values.{field_id}, in the words of its kind: “Write at most 2000 characters in Serial number.”, “Price takes a number.”, “Bought on takes a date written as year, month and day, like 2005-03-24.”, “Insured takes yes or no.”, “Condition takes one of its own choices.” or “Comfort takes a rating from one to five.” A field of another type, or of another account, answers “This field is not a field of the type of this possession.”',
                'The change is written to the account log.',
            ],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam],
            'bodyParams' => [
                ['name' => 'values', 'type' => 'object', 'required' => true, 'description' => 'The values to write, keyed by the field_id of each field. Text takes up to 2,000 characters, a number takes a number, a date is written as 2005-03-24, a boolean takes true or false, a select field takes one of its own choices, and a rating takes an integer from 1 to 5.', 'example' => [$paymentId => 'Card', $conditionId => 'Used', $serialId => 'SG-2005-0324']],
            ],
            'returns' => 'The possession object as it now reads. A vault or a possession of another account answers 404.',
            'response' => ['data' => $mug],
        ],
    ],
];
