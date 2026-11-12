<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();
$application = rtrim((string) config('app.url'), '/');

$rateLimit = 'Sixty requests per minute, per user, shared by every key and device.';

$vaultSlug = 'schrute-farms';
$pressId = '5e1b7c93-4a28-4d6f-9b02-7c3e8a1f4d60';
$barnStayId = '3b9e4f27-8d61-4c0a-a5f3-1e7c2d9b6a48';
$cellarStayId = 'f1d72a8c-5e39-4b16-9c04-7a3e6b2d8f15';
$barnId = '9c4a1e7f-2b58-4d36-8e09-5f3b7a1c6d24';
$cellarId = '6e2f8b31-7c4d-4a95-b1e8-3d9c5a7f2e60';

$webLink = 'The Locations section of the possession in the application, which opens for somebody signed in rather than for a key. It is the link to give a person.';

$barnStay = [
    'type' => 'possession_location_stay',
    'id' => $barnStayId,
    'attributes' => [
        'location_id' => $barnId,
        'location_name' => 'Barn',
        'arrived_on' => '2019-11-08',
        'left_on' => '2024-06-04',
        'is_current' => false,
        'reason' => 'After the harvest',
        'note' => null,
        'created_at' => 1773446400,
    ],
    'links' => [
        'self' => "{$base}/vaults/{$vaultSlug}/possessions/{$pressId}/location-stays/{$barnStayId}",
        'web' => "{$application}/vaults/{$vaultSlug}/possessions/{$pressId}/locations",
    ],
];

$cellarStay = [
    'type' => 'possession_location_stay',
    'id' => $cellarStayId,
    'attributes' => [
        'location_id' => $cellarId,
        'location_name' => 'Cellar',
        'arrived_on' => '2024-06-04',
        'left_on' => null,
        'is_current' => true,
        'reason' => 'Harvest season',
        'note' => 'Mose carried it down.',
        'created_at' => 1773450000,
    ],
    'links' => [
        'self' => "{$base}/vaults/{$vaultSlug}/possessions/{$pressId}/location-stays/{$cellarStayId}",
        'web' => "{$application}/vaults/{$vaultSlug}/possessions/{$pressId}/locations",
    ],
];

$attributes = [
    ['name' => 'location_id', 'type' => 'uuid', 'description' => 'The identifier of the location of the account where the possession stayed. Its full path comes from the locations endpoints.'],
    ['name' => 'location_name', 'type' => 'string', 'description' => 'The name of that location, without the locations around it.'],
    ['name' => 'arrived_on', 'type' => 'date', 'description' => 'The day it arrived, as YYYY-MM-DD, or null when the day is unknown.'],
    ['name' => 'left_on', 'type' => 'date', 'description' => 'The day it left, as YYYY-MM-DD, or null on the stay where it is now.'],
    ['name' => 'is_current', 'type' => 'boolean', 'description' => 'Whether it is the open stay, where the possession is now. A possession has at most one.'],
    ['name' => 'reason', 'type' => 'string', 'description' => 'Why it went there, or null.'],
    ['name' => 'note', 'type' => 'string', 'description' => 'Anything worth knowing about the stay, or null.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the stay was recorded, as a count of seconds since 1970.'],
];

$vaultParam = ['name' => 'vault', 'type' => 'string', 'required' => true, 'description' => 'The vault the possession is kept in, named either by the readable name it reports as slug or by the identifier it reports as id. A vault of another account answers 404.'];
$possessionParam = ['name' => 'possession', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of the possession. A possession of another vault or of another account answers 404.'];
$stayParam = ['name' => 'stay', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of the stay. A stay of another possession answers 404.'];

return [
    'name' => 'Possession locations',
    'description' => 'Where a possession has been kept since it was recorded. Each stay is one location of the account and the days the possession spent there. The open stay, with no day it left, is where the possession is now, and it is always the location the possession reports.',
    'sections' => [
        [
            'id' => 'possession-location-stays-index',
            'returnFields' => [
                ...ApiDocsFields::all('possession_location_stay', 'possession location stay', 'possession location stays', $attributes),
                ['name' => 'data[].links.web', 'type' => 'string', 'description' => $webLink],
            ],
            'title' => 'List the stays of a possession',
            'label' => 'List stays',
            'method' => 'GET',
            'path' => '/vaults/{vault}/possessions/{possession}/location-stays',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$pressId}/location-stays",
            'description' => 'List every stay of a possession, oldest first.',
            'body' => ['Stays whose arrival is unknown come first. On the same day, the earliest recorded comes first. The list is whole rather than paginated.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam],
            'returns' => 'Every stay object of the possession, or an empty list. A vault or a possession of another account answers 404.',
            'response' => ['data' => [$barnStay, $cellarStay]],
        ],
        [
            'id' => 'possession-location-stays-show',
            'returnFields' => ApiDocsFields::one('possession_location_stay', 'possession location stay', $attributes, webLink: $webLink),
            'title' => 'Get a stay',
            'method' => 'GET',
            'path' => '/vaults/{vault}/possessions/{possession}/location-stays/{stay}',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$pressId}/location-stays/{$cellarStayId}",
            'description' => 'Retrieve one stay of a possession.',
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam, $stayParam],
            'returns' => 'A stay object. A vault, a possession or a stay of another account answers 404.',
            'response' => ['data' => $cellarStay],
        ],
        [
            'id' => 'possession-location-stays-create',
            'returnFields' => ApiDocsFields::one('possession_location_stay', 'possession location stay', $attributes, webLink: $webLink),
            'title' => 'Move a possession',
            'method' => 'POST',
            'path' => '/vaults/{vault}/possessions/{possession}/location-stays',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$pressId}/location-stays",
            'description' => 'Move a possession to another location of the account on a given day.',
            'body' => [
                'The stay where it is now closes on that day, and a new stay opens at the location on the same day. The possession then reports that location as where it is. The change is written to the account log.',
                'The location it is already in answers 422 on location_id ("It is already in Barn."). A day before the current stay began answers 422 on moved_on ("The current stay began on 8 November 2019. Date the move after that, or correct the current stay instead.").',
            ],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam],
            'bodyParams' => [
                ['name' => 'location_id', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of a location of the account. A location of another account answers 422.', 'example' => $cellarId],
                ['name' => 'moved_on', 'type' => 'date', 'required' => true, 'description' => 'The day it moved, as YYYY-MM-DD.', 'example' => '2024-06-04'],
                ['name' => 'reason', 'type' => 'string', 'required' => false, 'description' => 'Why it moved, up to 255 characters. Markup is stripped.', 'example' => 'Harvest season'],
                ['name' => 'note', 'type' => 'string', 'required' => false, 'description' => 'Anything worth knowing about the new stay, up to 5,000 characters.', 'example' => 'Mose carried it down.'],
            ],
            'returns' => 'The new stay object, where the possession is now.',
            'responseStatus' => 201,
            'response' => ['data' => $cellarStay],
        ],
        [
            'id' => 'possession-location-stays-update',
            'returnFields' => ApiDocsFields::one('possession_location_stay', 'possession location stay', $attributes, webLink: $webLink),
            'title' => 'Correct a stay',
            'method' => 'PUT',
            'path' => '/vaults/{vault}/possessions/{possession}/location-stays/{stay}',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$pressId}/location-stays/{$barnStayId}",
            'description' => 'Correct one stay of a possession.',
            'body' => [
                'What you leave out keeps its value, and what you send as null is cleared. Only this stay changes: the stays before and after it are not moved. Correcting the location of the open stay changes where the possession is now. The change is written to the account log.',
                'A past stay without a day it left answers 422 on left_on ("Enter the day it left."), as does a day it left before the day it arrived ("This is before the day it arrived."). The open stay cannot be given a day it left ("Move the possession to close the current stay.").',
            ],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam, $stayParam],
            'bodyParams' => [
                ['name' => 'location_id', 'type' => 'uuid', 'required' => false, 'description' => 'The identifier of a location of the account.'],
                ['name' => 'arrived_on', 'type' => 'date', 'required' => false, 'description' => 'The day it arrived, as YYYY-MM-DD, or null when unknown.'],
                ['name' => 'left_on', 'type' => 'date', 'required' => false, 'description' => 'The day it left, as YYYY-MM-DD. Only for a past stay.'],
                ['name' => 'reason', 'type' => 'string', 'required' => false, 'description' => 'Why it was there, up to 255 characters.'],
                ['name' => 'note', 'type' => 'string', 'required' => false, 'description' => 'Anything worth knowing about the stay, up to 5,000 characters.'],
            ],
            'exampleBody' => ['reason' => 'After the harvest'],
            'returns' => 'The stay object, as it now stands. A stay of another possession or of another account answers 404.',
            'response' => ['data' => $barnStay],
        ],
        [
            'id' => 'possession-location-stays-destroy',
            'title' => 'Delete a stay',
            'method' => 'DELETE',
            'path' => '/vaults/{vault}/possessions/{possession}/location-stays/{stay}',
            'examplePath' => "/vaults/{$vaultSlug}/possessions/{$pressId}/location-stays/{$barnStayId}",
            'description' => 'Delete one stay of a possession.',
            'body' => ['The deletion is final. The other stays are not moved, so its period shows as unrecorded. Deleting the open stay leaves the possession with no current location until it is moved. The location itself stays. The account log keeps the name of the possession.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$vaultParam, $possessionParam, $stayParam],
            'returns' => 'Nothing. A stay of another possession or of another account answers 404.',
            'responseStatus' => 204,
            'response' => [],
        ],
    ],
];
