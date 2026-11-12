<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();
$application = rtrim((string) config('app.url'), '/');

$rateLimit = 'Sixty requests per minute, per user, shared by every key and device.';

$webLink = 'The settings screen where the tags of the account are managed, which opens for somebody signed in rather than for a key. It is the link to give a person.';

$tagId = '7a3e9c15-2b84-4d60-9f17-c5e8a2d4b031';

$insured = [
    'type' => 'possession_tag',
    'id' => $tagId,
    'attributes' => [
        'name' => 'Insured',
        'colour' => '#2f7f86',
        'created_at' => 1761436800,
        'updated_at' => 1761440400,
    ],
    'links' => [
        'self' => "{$base}/possession-tags/{$tagId}",
        'web' => "{$application}/settings/account/possession-tags",
    ],
];

$newTag = $insured;
$newTag['attributes']['name'] = 'Heirloom';
$newTag['attributes']['colour'] = '#3f5fd0';
$newTag['attributes']['updated_at'] = 1761436800;

$tagAttributes = [
    ['name' => 'name', 'type' => 'string', 'description' => 'What the tag reads, such as Insured or To sell.'],
    ['name' => 'colour', 'type' => 'string', 'description' => 'One of the eight vault colours, as a hexadecimal value.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the tag was added, as a count of seconds since 1970.'],
    ['name' => 'updated_at', 'type' => 'timestamp', 'description' => 'When its name or colour last changed, as a count of seconds, or null.'],
];

$one = ApiDocsFields::one('possession_tag', 'possession tag', $tagAttributes, webLink: $webLink);

$tagParam = ['name' => 'possessionTag', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of the possession tag. A tag belonging to somebody else answers 404.'];

$nameRefusal = 'Up to 50 characters. Markup and surrounding blank space are stripped, and a name left with nothing in it is refused. A name another tag of the account already has, ignoring case, is refused with “You already have a tag with this name.”';

$colours = 'One of the vault colours: #3f5fd0, #4f8a49, #9a6a24, #9a3f2b, #6b4fa8, #2f7f86, #a8447a or #4a4a52.';

return [
    'name' => 'Possession tags',
    'description' => 'The words your account puts on its possessions, to sort them across types and locations.',
    'sections' => [
        [
            'id' => 'possession-tags-index',
            'returnFields' => ApiDocsFields::page('possession_tag', 'possession tag', 'possession tags', $tagAttributes, webLink: $webLink),
            'title' => 'List possession tags',
            'method' => 'GET',
            'path' => '/possession-tags',
            'description' => 'List every possession tag of your account, sorted by name.',
            'body' => ['A possession tag belongs to the account, not to a vault, as possession types do.'],
            'rateLimit' => $rateLimit,
            'queryParams' => [
                ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Which page to answer with.', 'default' => '1'],
                ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'How many tags one page holds, between 1 and 100. A number outside the range is brought back into it.', 'default' => '25'],
            ],
            'returns' => 'A page of possession tag objects, and how many there are in all. An account with none answers an empty list.',
            'response' => ['data' => [$insured], 'meta' => ['page' => 1, 'per_page' => 25, 'total' => 1]],
        ],
        [
            'id' => 'possession-tags-show',
            'returnFields' => $one,
            'title' => 'Get a possession tag',
            'method' => 'GET',
            'path' => '/possession-tags/{possessionTag}',
            'examplePath' => "/possession-tags/{$tagId}",
            'description' => 'Retrieve one possession tag.',
            'rateLimit' => $rateLimit,
            'pathParams' => [$tagParam],
            'returns' => 'A possession tag object. A tag belonging to somebody else answers 404.',
            'response' => ['data' => $insured],
        ],
        [
            'id' => 'possession-tags-create',
            'returnFields' => $one,
            'title' => 'Add a possession tag',
            'method' => 'POST',
            'path' => '/possession-tags',
            'description' => 'Add a word your account can put on its possessions.',
            'body' => ['The change is written to the account log.'],
            'rateLimit' => $rateLimit,
            'bodyParams' => [
                ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => "What the tag reads. {$nameRefusal}", 'example' => 'Heirloom'],
                ['name' => 'colour', 'type' => 'string', 'required' => false, 'description' => "{$colours} Blue when left out.", 'example' => '#3f5fd0'],
            ],
            'returns' => 'The possession tag object that was added.',
            'responseStatus' => 201,
            'response' => ['data' => $newTag],
        ],
        [
            'id' => 'possession-tags-update',
            'returnFields' => $one,
            'title' => 'Change a possession tag',
            'method' => 'PUT',
            'path' => '/possession-tags/{possessionTag}',
            'examplePath' => "/possession-tags/{$tagId}",
            'description' => 'Rename a possession tag and set its colour. Both are required.',
            'body' => ['Changing only the case of its own name is allowed. The change is written to the account log.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$tagParam],
            'bodyParams' => [
                ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => "The new name. {$nameRefusal}", 'example' => 'Insured'],
                ['name' => 'colour', 'type' => 'string', 'required' => true, 'description' => $colours, 'example' => '#2f7f86'],
            ],
            'returns' => 'The possession tag object as it now reads.',
            'response' => ['data' => $insured],
        ],
        [
            'id' => 'possession-tags-destroy',
            'title' => 'Delete a possession tag',
            'method' => 'DELETE',
            'path' => '/possession-tags/{possessionTag}',
            'examplePath' => "/possession-tags/{$tagId}",
            'description' => 'Delete a possession tag.',
            'body' => ['The deletion is final. The account log keeps the name of the tag.'],
            'rateLimit' => $rateLimit,
            'pathParams' => [$tagParam],
            'returns' => 'Nothing. A tag belonging to somebody else answers 404.',
            'responseStatus' => 204,
            'response' => [],
        ],
    ],
];
