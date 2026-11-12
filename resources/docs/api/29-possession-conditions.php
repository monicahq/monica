<?php

declare(strict_types=1);

use App\Models\Section;
use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();
$application = rtrim((string) config('app.url'), '/');

$rateLimit = 'Sixty requests per minute, per user, shared by every key and device.';

$webLink = 'The settings screen where the conditions of the account are managed, which opens for somebody signed in rather than for a key. It is the link to give a person.';

$conditionId = '4c1e7a92-8d35-4f06-b2a9-e3f71c5d8b40';

$likeNew = [
    'type' => 'possession_condition',
    'id' => $conditionId,
    'attributes' => [
        'name' => 'Like new',
        'colour' => '#2f7f86',
        'icon' => null,
        'position' => 2,
        'created_at' => 1761436800,
        'updated_at' => 1761440400,
    ],
    'links' => [
        'self' => "{$base}/possession-conditions/{$conditionId}",
        'web' => "{$application}/settings/account/possession-conditions",
    ],
];

$mint = $likeNew;
$mint['attributes']['name'] = 'Mint';
$mint['attributes']['icon'] = 'star';
$mint['attributes']['position'] = 7;
$mint['attributes']['updated_at'] = 1761436800;

$wellLoved = $likeNew;
$wellLoved['attributes']['name'] = 'Well loved';
$wellLoved['attributes']['colour'] = '#9a6a24';
$wellLoved['attributes']['icon'] = 'heart';
$wellLoved['attributes']['position'] = 4;

$moved = $mint;
$moved['attributes']['position'] = 1;
$moved['attributes']['updated_at'] = 1761440400;

$conditionAttributes = [
    ['name' => 'name', 'type' => 'string', 'description' => 'What the condition reads, such as New or Broken.'],
    ['name' => 'colour', 'type' => 'string', 'description' => 'One of the eight vault colours, as a hexadecimal value.'],
    ['name' => 'icon', 'type' => 'string', 'description' => 'One of the glyphs of the catalogue, or null when the condition has none.'],
    ['name' => 'position', 'type' => 'integer', 'description' => 'Its place in the order the account keeps, from best to worst, counting from 1.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the condition was added, as a count of seconds since 1970.'],
    ['name' => 'updated_at', 'type' => 'timestamp', 'description' => 'When it last changed, as a count of seconds, or null.'],
];

$one = ApiDocsFields::one('possession_condition', 'possession condition', $conditionAttributes, webLink: $webLink);

$conditionParam = ['name' => 'possessionCondition', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of the possession condition. A condition belonging to somebody else answers 404.'];

$nameRefusal = 'Up to 50 characters. Markup and surrounding blank space are stripped, and a name left with nothing in it is refused. A name another condition of the account already has, ignoring case, is refused with “You already have a condition with this name.”';

$colours = 'One of the vault colours: #3f5fd0, #4f8a49, #9a6a24, #9a3f2b, #6b4fa8, #2f7f86, #a8447a or #4a4a52.';

$icons = 'One of the glyphs of the catalogue: '.implode(', ', Section::ICONS).'.';

return [
    'name' => 'Possession conditions',
    'description' => 'The states the possessions of your account can be in, from best to worst.',
    'sections' => [
        [
            'id' => 'possession-conditions-index',
            'returnFields' => ApiDocsFields::page('possession_condition', 'possession condition', 'possession conditions', $conditionAttributes, webLink: $webLink),
            'title' => 'List possession conditions',
            'method' => 'GET',
            'path' => '/possession-conditions',
            'description' => 'List every possession condition of your account, in the order the account keeps them.',
            'body' => ['A possession condition belongs to the account, not to a vault. Every account starts with six: New, Like new, Used, Worn, Damaged and Broken, in the language of the person who created it.'],
            'rateLimit' => $rateLimit,
            'queryParams' => [
                ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Which page to answer with.', 'default' => '1'],
                ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'How many conditions one page holds, between 1 and 100. A number outside the range is brought back into it.', 'default' => '25'],
            ],
            'returns' => 'A page of possession condition objects, and how many there are in all. An account with none answers an empty list.',
            'response' => ['data' => [$likeNew], 'meta' => ['page' => 1, 'per_page' => 25, 'total' => 1]],
        ],
        [
            'id' => 'possession-conditions-show',
            'returnFields' => $one,
            'title' => 'Get a possession condition',
            'method' => 'GET',
            'path' => '/possession-conditions/{possessionCondition}',
            'examplePath' => "/possession-conditions/{$conditionId}",
            'description' => 'Retrieve one possession condition.',
            'rateLimit' => $rateLimit,
            'pathParams' => [$conditionParam],
            'returns' => 'A possession condition object. A condition belonging to somebody else answers 404.',
            'response' => ['data' => $likeNew],
        ],
        [
            'id' => 'possession-conditions-create',
            'returnFields' => $one,
            'title' => 'Add a possession condition',
            'method' => 'POST',
            'path' => '/possession-conditions',
            'description' => 'Add a state the possessions of your account can be in. It goes last in the order.',
            'body' => ['The change is written to the account log.'],
            'rateLimit' => $rateLimit,
            'bodyParams' => [
                ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => "What the condition reads. {$nameRefusal}", 'example' => 'Mint'],
                ['name' => 'colour', 'type' => 'string', 'required' => false, 'description' => "{$colours} Blue when left out.", 'example' => '#2f7f86'],
                ['name' => 'icon', 'type' => 'string', 'required' => false, 'description' => "{$icons} None when left out.", 'example' => 'star'],
            ],
            'returns' => 'The possession condition object that was added.',
            'responseStatus' => 201,
            'response' => ['data' => $mint],
        ],
        [
            'id' => 'possession-conditions-update',
            'returnFields' => $one,
            'title' => 'Change a possession condition',
            'method' => 'PUT',
            'path' => '/possession-conditions/{possessionCondition}',
            'examplePath' => "/possession-conditions/{$conditionId}",
            'description' => 'Rename a possession condition, set its colour and its icon. The name and the colour are required; an icon left out or null removes it.',
            'body' => ['The condition keeps its place in the order. Changing only the case of its own name is allowed. The change is written to the account log.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$conditionParam],
            'bodyParams' => [
                ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => "The new name. {$nameRefusal}", 'example' => 'Well loved'],
                ['name' => 'colour', 'type' => 'string', 'required' => true, 'description' => $colours, 'example' => '#9a6a24'],
                ['name' => 'icon', 'type' => 'string', 'required' => false, 'description' => "{$icons} Left out or null removes the icon.", 'example' => 'heart'],
            ],
            'returns' => 'The possession condition object as it now reads.',
            'response' => ['data' => $wellLoved],
        ],
        [
            'id' => 'possession-conditions-position',
            'returnFields' => $one,
            'title' => 'Move a possession condition',
            'method' => 'PUT',
            'path' => '/possession-conditions/{possessionCondition}/position',
            'examplePath' => "/possession-conditions/{$conditionId}/position",
            'description' => 'Move a possession condition to another place in the order. The others shift to make room.',
            'body' => ['A position past the end puts the condition last. The change is written to the account log.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$conditionParam],
            'bodyParams' => [
                ['name' => 'position', 'type' => 'integer', 'required' => true, 'description' => 'Where it goes, counting from 1.', 'example' => 1],
            ],
            'returns' => 'The possession condition object at its new place.',
            'response' => ['data' => $moved],
        ],
        [
            'id' => 'possession-conditions-destroy',
            'title' => 'Delete a possession condition',
            'method' => 'DELETE',
            'path' => '/possession-conditions/{possessionCondition}',
            'examplePath' => "/possession-conditions/{$conditionId}",
            'description' => 'Delete a possession condition. The ones after it move up to close the gap.',
            'body' => ['The deletion is final. The account log keeps the name of the condition.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$conditionParam],
            'returns' => 'Nothing. A condition belonging to somebody else answers 404.',
            'responseStatus' => 204,
            'response' => [],
        ],
    ],
];
