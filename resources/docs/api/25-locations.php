<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();
$application = rtrim((string) config('app.url'), '/');

$webLink = 'The screen where the location is edited in the application, which opens for somebody signed in rather than for a key. It is the link to give a person.';

$scranton = [
    'type' => 'location',
    'id' => '5b7e2c90-4d18-4a3f-9e61-0c8d2f7a1b43',
    'attributes' => [
        'parent_id' => null,
        'name' => 'Dunder Mifflin Scranton',
        'emoji' => '🏢',
        'address' => "1725 Slough Avenue\nScranton, PA 18505",
        'path' => 'Dunder Mifflin Scranton',
        'effective_address' => [
            'address' => "1725 Slough Avenue\nScranton, PA 18505",
            'location_id' => '5b7e2c90-4d18-4a3f-9e61-0c8d2f7a1b43',
            'location_name' => 'Dunder Mifflin Scranton',
        ],
        'location_count' => 1,
        'created_at' => 1761350400,
        'updated_at' => 1761354000,
    ],
    'links' => [
        'self' => $base.'/locations/5b7e2c90-4d18-4a3f-9e61-0c8d2f7a1b43',
        'web' => $application.'/settings/account/locations/5b7e2c90-4d18-4a3f-9e61-0c8d2f7a1b43/edit',
    ],
];

$warehouse = [
    'type' => 'location',
    'id' => 'e83a1f6d-27c4-4b95-8d02-6f1b9c4e7a58',
    'attributes' => [
        'parent_id' => '5b7e2c90-4d18-4a3f-9e61-0c8d2f7a1b43',
        'name' => 'Warehouse',
        'emoji' => '📦',
        'address' => null,
        'path' => 'Dunder Mifflin Scranton › Warehouse',
        'effective_address' => [
            'address' => "1725 Slough Avenue\nScranton, PA 18505",
            'location_id' => '5b7e2c90-4d18-4a3f-9e61-0c8d2f7a1b43',
            'location_name' => 'Dunder Mifflin Scranton',
        ],
        'location_count' => 0,
        'created_at' => 1761354000,
        'updated_at' => 1761357600,
    ],
    'links' => [
        'self' => $base.'/locations/e83a1f6d-27c4-4b95-8d02-6f1b9c4e7a58',
        'web' => $application.'/settings/account/locations/e83a1f6d-27c4-4b95-8d02-6f1b9c4e7a58/edit',
    ],
];

$identifier = [
    ['name' => 'location', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of the location, which is the id it comes back with. A location belonging to somebody else answers 404.'],
];

$locationAttributes = [
    ['name' => 'parent_id', 'type' => 'uuid', 'description' => 'The location it sits inside, or null at the top level.'],
    ['name' => 'name', 'type' => 'string', 'description' => 'What the location is called: a town, a house, a room, a shelf, a box. Two locations may share a name; the path tells them apart.'],
    ['name' => 'emoji', 'type' => 'string', 'description' => 'One emoji shown in place of the default icon, or null for none.'],
    ['name' => 'address', 'type' => 'string', 'description' => 'The address written on the location itself, as free text over several lines, or null. Nothing geocodes it.'],
    ['name' => 'path', 'type' => 'string', 'description' => 'Every location from the top level down to this one, joined by " › ". It is how a location is named wherever one is chosen.'],
    ['name' => 'effective_address', 'type' => 'object', 'description' => 'The address that applies to the location: its own, or else that of the nearest location around it that has one. Null when none of them has an address.'],
    ['name' => 'effective_address.address', 'type' => 'string', 'description' => 'The address, as it was written.'],
    ['name' => 'effective_address.location_id', 'type' => 'uuid', 'description' => 'The location the address is written on, which is this one when it has its own.'],
    ['name' => 'effective_address.location_name', 'type' => 'string', 'description' => 'The name of that location.'],
    ['name' => 'location_count', 'type' => 'integer', 'description' => 'How many locations sit directly inside this one. A location holding any cannot be deleted.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the location was added, as a count of seconds since 1970 rather than a written date.'],
    ['name' => 'updated_at', 'type' => 'timestamp', 'description' => 'When it last changed, as a count of seconds. Equal to created_at until it is changed.'],
];

$bodyParams = [
    ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => 'What the location is called. Up to 255 characters. Markup and surrounding blank space are stripped, and a name left with nothing in it is refused.', 'example' => 'Warehouse'],
    ['name' => 'parent_id', 'type' => 'uuid', 'required' => false, 'description' => 'The location it sits inside. Left out or null, it sits at the top level. A location of somebody else is refused, and so is the location itself or one inside it.', 'example' => '5b7e2c90-4d18-4a3f-9e61-0c8d2f7a1b43'],
    ['name' => 'emoji', 'type' => 'string', 'required' => false, 'description' => 'Exactly one emoji. A family, a flag or a skin tone counts as one. Letters, or two emoji, are refused.', 'example' => '📦'],
    ['name' => 'address', 'type' => 'string', 'required' => false, 'description' => 'Free text over several lines, up to 2,000 characters. Left out, the location uses the address of the nearest location around it that has one.'],
];

return [
    'name' => 'Locations',
    'description' => 'The places the things of your account are kept in, each of them inside another as deep as you like.',
    'sections' => [
        [
            'id' => 'locations-index',
            'returnFields' => ApiDocsFields::page('location', 'location', 'locations', $locationAttributes, webLink: $webLink),
            'title' => 'List locations',
            'label' => 'List locations',
            'method' => 'GET',
            'path' => '/locations',
            'description' => 'List every location of your account, each one followed by the locations inside it.',
            'body' => [
                'A location is a place: a town, a house, a room, a shelf, a box. It belongs to the account, not to a vault, since one house holds the things of every vault.',
                'The order is the one the settings screen shows: each location comes before the locations inside it, and the locations at each level are sorted by name. Paging slices that order, so a page may begin inside a location listed on the page before.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'queryParams' => [
                ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Which page to answer with.', 'default' => '1'],
                ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'How many locations one page holds, between 1 and 100. A number outside the range is brought back into it rather than refused.', 'default' => '25'],
            ],
            'returns' => 'A page of location objects, and how many there are in all. An account with no location answers an empty list.',
            'response' => [
                'data' => [$scranton, $warehouse],
                'meta' => ['page' => 1, 'per_page' => 25, 'total' => 2],
            ],
        ],
        [
            'id' => 'locations-show',
            'returnFields' => ApiDocsFields::one('location', 'location', $locationAttributes, webLink: $webLink),
            'title' => 'Get a location',
            'label' => 'Get a location',
            'method' => 'GET',
            'path' => '/locations/{location}',
            'examplePath' => '/locations/e83a1f6d-27c4-4b95-8d02-6f1b9c4e7a58',
            'description' => 'Retrieve one location, its full path and the address that applies to it.',
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $identifier,
            'returns' => 'A location object. A location belonging to somebody else answers 404, which is also what a location that never existed answers.',
            'response' => ['data' => $warehouse],
        ],
        [
            'id' => 'locations-create',
            'returnFields' => ApiDocsFields::one('location', 'location', $locationAttributes, webLink: $webLink),
            'title' => 'Add a location',
            'label' => 'Add a location',
            'method' => 'POST',
            'path' => '/locations',
            'description' => 'Add a location at the top level, or inside another one.',
            'body' => [
                'Start with a home or a building, then add the rooms and the furniture inside it, sending the id of each as the parent_id of the next.',
                'The change is written to the account log.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'bodyParams' => $bodyParams,
            'returns' => 'The location object that was added.',
            'responseStatus' => 201,
            'response' => ['data' => $warehouse],
        ],
        [
            'id' => 'locations-update',
            'returnFields' => ApiDocsFields::one('location', 'location', $locationAttributes, webLink: $webLink),
            'title' => 'Change a location',
            'label' => 'Change a location',
            'method' => 'PUT',
            'path' => '/locations/{location}',
            'examplePath' => '/locations/e83a1f6d-27c4-4b95-8d02-6f1b9c4e7a58',
            'description' => 'Rename a location, move it inside another one or to the top level, or change its emoji or its address.',
            'body' => [
                'The body describes the whole location, as the form does. A parent_id, an emoji or an address left out is cleared, so send the ones you want to keep.',
                'Everything inside the location moves with it, and every path containing it changes. A parent that is the location itself or one inside it, at any depth, answers 422 on parent_id: "A location cannot sit inside itself, or inside a location it holds."',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $identifier,
            'bodyParams' => $bodyParams,
            'returns' => 'The location object as it now reads.',
            'response' => ['data' => $warehouse],
        ],
        [
            'id' => 'locations-destroy',
            'title' => 'Delete a location',
            'label' => 'Delete a location',
            'method' => 'DELETE',
            'path' => '/locations/{location}',
            'examplePath' => '/locations/e83a1f6d-27c4-4b95-8d02-6f1b9c4e7a58',
            'description' => 'Remove a location that holds no other and that no possession has stayed at.',
            'body' => [
                'A location holding others answers 422 on location, saying how many and that they must be moved first, for instance "1 location is inside it. Move it first." Nothing is moved on your behalf.',
                'A location any stay of a possession refers to, current or past, answers 422 on location as well, for instance "1 stay of a possession is here. Correct or delete it first." The history of a possession is never lost on your behalf.',
                'The deletion is final, and the account log keeps the name of the location.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $identifier,
            'returns' => 'Nothing. A location holding others or referred to by a stay answers 422, and one belonging to somebody else answers 404.',
            'responseStatus' => 204,
            'response' => [],
        ],
    ],
];
