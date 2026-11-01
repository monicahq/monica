<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();

$vaultSlug = 'dunder-mifflin';
$contactSlug = 'angela-martin';
$groupId = '5d2a7c91-3e48-4b06-9f1d-a83c60e2b4f7';
$membershipId = 'c81f4e2a-6b97-4d35-a0e8-2f5b913d7c64';

$group = [
    'type' => 'group',
    'id' => $groupId,
    'attributes' => [
        'name' => 'Party planning committee',
        'description' => 'Plans every birthday, retirement and holiday party of the Scranton branch.',
        'started_on' => '2005-01-01',
        'started_precision' => 'year',
        'ended_on' => null,
        'ended_precision' => 'unknown',
        'current_member_count' => 3,
        'created_at' => 1791547200,
        'updated_at' => 1791547200,
        'deleted_at' => null,
    ],
    'links' => [
        'self' => $base.'/vaults/'.$vaultSlug.'/groups/'.$groupId,
    ],
];

$membership = [
    'type' => 'group_membership',
    'id' => $membershipId,
    'attributes' => [
        'group' => [
            'id' => $groupId,
            'name' => 'Party planning committee',
        ],
        'contact' => [
            'id' => '3b9e1f70-4c2d-4a86-b5e3-7d0a62f8c915',
            'slug' => 'angela-martin',
            'name' => 'Angela Martin',
        ],
        'role' => 'Chair',
        'joined_on' => '2005-03-01',
        'joined_precision' => 'month',
        'left_on' => null,
        'left_precision' => 'unknown',
        'is_current' => true,
        'created_at' => 1791547200,
        'updated_at' => 1791547200,
    ],
];

$flagNote = 'Groups are not yet available to every account. Until they are available to yours, every endpoint here answers 404.';

$vaultIdentifier = [
    ['name' => 'vault', 'type' => 'string', 'required' => true, 'description' => 'The vault the groups live in, named either by the readable name it reports as slug or by the identifier it reports as id.'],
];

$groupIdentifier = [
    ...$vaultIdentifier,
    ['name' => 'group', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of the group. One addressed through the wrong vault, or belonging to another account, answers 404.'],
];

$membershipIdentifier = [
    ...$groupIdentifier,
    ['name' => 'membership', 'type' => 'uuid', 'required' => true, 'description' => 'The identifier of the membership. One addressed through another group answers 404.'],
];

$pageParams = [
    ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Which page to answer with.', 'default' => '1'],
    ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'How many records one page holds, between 1 and 100. A number outside the range is brought back into it rather than refused.', 'default' => '25'],
];

$precisionDescription = 'How much of the date is known: day, month, year or decade. Always unknown when there is no date.';

$groupAttributes = [
    ['name' => 'name', 'type' => 'string', 'description' => 'What the group is called.'],
    ['name' => 'description', 'type' => 'string', 'description' => 'What was written about it, or null.'],
    ['name' => 'started_on', 'type' => 'string', 'description' => 'When it started, written as a year, a month and a day, or null.'],
    ['name' => 'started_precision', 'type' => 'string', 'description' => $precisionDescription],
    ['name' => 'ended_on', 'type' => 'string', 'description' => 'When it ended, written as a year, a month and a day, or null while it still exists.'],
    ['name' => 'ended_precision', 'type' => 'string', 'description' => $precisionDescription],
    ['name' => 'current_member_count', 'type' => 'integer', 'description' => 'How many people are in it today. Those who left are not counted.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When it was created, as a count of seconds since 1970.'],
    ['name' => 'updated_at', 'type' => 'timestamp', 'description' => 'When it last changed, as a count of seconds since 1970, or null.'],
    ['name' => 'deleted_at', 'type' => 'timestamp', 'description' => 'When it was deleted, as a count of seconds since 1970, or null.'],
];

$membershipAttributes = [
    ['name' => 'group', 'type' => 'object', 'description' => 'The group the membership is in.'],
    ['name' => 'group.id', 'type' => 'uuid', 'description' => 'The identifier of the group.'],
    ['name' => 'group.name', 'type' => 'string', 'description' => 'What the group is called.'],
    ['name' => 'contact', 'type' => 'object', 'description' => 'The person the membership is about.'],
    ['name' => 'contact.id', 'type' => 'uuid', 'description' => 'The identifier of the contact.'],
    ['name' => 'contact.slug', 'type' => 'string', 'description' => 'The readable name of the contact, which addresses it as well as its id.'],
    ['name' => 'contact.name', 'type' => 'string', 'description' => 'The name of the contact, as it is shown.'],
    ['name' => 'role', 'type' => 'string', 'description' => 'What the person is in the group, in their own words, or null.'],
    ['name' => 'joined_on', 'type' => 'string', 'description' => 'When the person joined, written as a year, a month and a day, or null.'],
    ['name' => 'joined_precision', 'type' => 'string', 'description' => $precisionDescription],
    ['name' => 'left_on', 'type' => 'string', 'description' => 'When the person left, written as a year, a month and a day, or null while they are still in the group.'],
    ['name' => 'left_precision', 'type' => 'string', 'description' => $precisionDescription],
    ['name' => 'is_current', 'type' => 'boolean', 'description' => 'True while the person is in the group, which is while left_on is null.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the membership was recorded, as a count of seconds since 1970.'],
    ['name' => 'updated_at', 'type' => 'timestamp', 'description' => 'When it last changed, as a count of seconds since 1970, or null.'],
];

$groupParams = [
    ['name' => 'description', 'type' => 'string', 'required' => false, 'description' => 'What the group is, up to 65,535 characters.', 'example' => 'Plans every birthday, retirement and holiday party of the Scranton branch.'],
    ['name' => 'started_on', 'type' => 'string', 'required' => false, 'description' => 'When it started, written as a year, a month and a day.', 'example' => '2005-01-01'],
    ['name' => 'started_precision', 'type' => 'string', 'required' => false, 'description' => 'How much of started_on is known: day, month, year, decade or unknown.', 'example' => 'year'],
    ['name' => 'ended_on', 'type' => 'string', 'required' => false, 'description' => 'When it ended, written as a year, a month and a day.'],
    ['name' => 'ended_precision', 'type' => 'string', 'required' => false, 'description' => 'How much of ended_on is known: day, month, year, decade or unknown.'],
];

$membershipParams = [
    ['name' => 'role', 'type' => 'string', 'required' => false, 'description' => 'What the person is in the group, in your own words, up to 255 characters.', 'example' => 'Chair'],
    ['name' => 'joined_on', 'type' => 'string', 'required' => false, 'description' => 'When the person joined, written as a year, a month and a day.', 'example' => '2005-03-01'],
    ['name' => 'joined_precision', 'type' => 'string', 'required' => false, 'description' => 'How much of joined_on is known: day, month, year, decade or unknown.', 'example' => 'month'],
    ['name' => 'left_on', 'type' => 'string', 'required' => false, 'description' => 'When the person left, written as a year, a month and a day. A date before joined_on answers 422.'],
    ['name' => 'left_precision', 'type' => 'string', 'required' => false, 'description' => 'How much of left_on is known: day, month, year, decade or unknown.'],
];

$contactGroupsFields = array_values(array_filter(
    ApiDocsFields::all('group_membership', 'membership', 'memberships of the contact', $membershipAttributes),
    fn (array $field): bool => ! str_starts_with($field['name'], 'data[].links'),
));

return [
    'name' => 'Groups',
    'description' => 'The people of a vault who belong together: a household, a couple, a team. A membership records who is in a group, in what role, and when they joined and left.',
    'sections' => [
        [
            'id' => 'groups-index',
            'flag' => 'groups',
            'returnFields' => ApiDocsFields::page('group', 'group', 'groups', $groupAttributes),
            'title' => 'List the groups of a vault',
            'label' => 'List groups',
            'method' => 'GET',
            'path' => '/vaults/{vault}/groups',
            'examplePath' => '/vaults/'.$vaultSlug.'/groups',
            'description' => 'List the groups of a vault, by name.',
            'body' => [
                'Deleted groups are left out.',
                $flagNote,
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $vaultIdentifier,
            'queryParams' => $pageParams,
            'returns' => 'A page of group objects, and how many there are in all. A vault of another account answers 404.',
            'response' => [
                'data' => [$group],
                'meta' => ['page' => 1, 'per_page' => 25, 'total' => 1],
            ],
        ],
        [
            'id' => 'groups-show',
            'flag' => 'groups',
            'returnFields' => ApiDocsFields::one('group', 'group', $groupAttributes),
            'title' => 'Get a group',
            'label' => 'Get a group',
            'method' => 'GET',
            'path' => '/vaults/{vault}/groups/{group}',
            'examplePath' => '/vaults/'.$vaultSlug.'/groups/'.$groupId,
            'description' => 'Retrieve one group, with the dates it existed between and how many people are in it today.',
            'body' => [
                'A deleted group answers 404.',
                $flagNote,
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $groupIdentifier,
            'returns' => 'A group object. One of another account or vault answers 404.',
            'response' => ['data' => $group],
        ],
        [
            'id' => 'groups-create',
            'flag' => 'groups',
            'returnFields' => ApiDocsFields::one('group', 'group', $groupAttributes),
            'title' => 'Create a group',
            'label' => 'Create a group',
            'method' => 'POST',
            'path' => '/vaults/{vault}/groups',
            'examplePath' => '/vaults/'.$vaultSlug.'/groups',
            'description' => 'Create a group in a vault. It starts with nobody in it.',
            'body' => [
                'A date sent without its precision is taken as known to the day. A precision sent without its date is ignored.',
                $flagNote,
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $vaultIdentifier,
            'bodyParams' => [
                ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => 'What the group is called, up to 255 characters.', 'example' => 'Party planning committee'],
                ...$groupParams,
            ],
            'returns' => 'The group object that was created.',
            'responseStatus' => 201,
            'response' => ['data' => $group],
        ],
        [
            'id' => 'groups-update',
            'flag' => 'groups',
            'returnFields' => ApiDocsFields::one('group', 'group', $groupAttributes),
            'title' => 'Change a group',
            'label' => 'Change a group',
            'method' => 'PUT',
            'path' => '/vaults/{vault}/groups/{group}',
            'examplePath' => '/vaults/'.$vaultSlug.'/groups/'.$groupId,
            'description' => 'Change the name, the description or the dates of a group.',
            'body' => [
                'Every field is optional. A field you leave out keeps its value, and a description or a date sent as null is cleared. A precision left out keeps the one the date had, or day when the date had none.',
                $flagNote,
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $groupIdentifier,
            'bodyParams' => [
                ['name' => 'name', 'type' => 'string', 'required' => false, 'description' => 'What the group is called, up to 255 characters.', 'example' => 'Party planning committee'],
                ...$groupParams,
            ],
            'returns' => 'The group object as it stands now.',
            'response' => ['data' => $group],
        ],
        [
            'id' => 'groups-destroy',
            'flag' => 'groups',
            'title' => 'Delete a group',
            'label' => 'Delete a group',
            'method' => 'DELETE',
            'path' => '/vaults/{vault}/groups/{group}',
            'examplePath' => '/vaults/'.$vaultSlug.'/groups/'.$groupId,
            'description' => 'Delete a group. It can be restored.',
            'body' => [
                'The people in it are not deleted. Its memberships are kept, and come back with it when it is restored.',
                $flagNote,
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $groupIdentifier,
            'returns' => 'Nothing at all.',
            'responseStatus' => 204,
        ],
        [
            'id' => 'groups-restoration-update',
            'flag' => 'groups',
            'returnFields' => ApiDocsFields::one('group', 'group', $groupAttributes),
            'title' => 'Restore a group',
            'label' => 'Restore a group',
            'method' => 'PUT',
            'path' => '/vaults/{vault}/groups/{group}/restoration',
            'examplePath' => '/vaults/'.$vaultSlug.'/groups/'.$groupId.'/restoration',
            'description' => 'Bring a deleted group back, with every membership it held.',
            'body' => [
                'A group that is not deleted answers 404.',
                $flagNote,
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $groupIdentifier,
            'returns' => 'The group object, restored.',
            'response' => ['data' => $group],
        ],
        [
            'id' => 'group-members-index',
            'flag' => 'groups',
            'returnFields' => ApiDocsFields::page('group_membership', 'membership', 'memberships', $membershipAttributes, withLinks: false),
            'title' => 'List the members of a group',
            'label' => 'List members',
            'method' => 'GET',
            'path' => '/vaults/{vault}/groups/{group}/members',
            'examplePath' => '/vaults/'.$vaultSlug.'/groups/'.$groupId.'/members',
            'description' => 'List the memberships of a group: the people in it today first, then those who left, the most recent departure first.',
            'body' => [
                'The memberships of deleted contacts are left out. A deleted group answers 404.',
                $flagNote,
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $groupIdentifier,
            'queryParams' => $pageParams,
            'returns' => 'A page of membership objects, and how many there are in all.',
            'response' => [
                'data' => [$membership],
                'meta' => ['page' => 1, 'per_page' => 25, 'total' => 1],
            ],
        ],
        [
            'id' => 'group-members-create',
            'flag' => 'groups',
            'returnFields' => ApiDocsFields::one('group_membership', 'membership', $membershipAttributes, withLinks: false),
            'title' => 'Add somebody to a group',
            'label' => 'Add a member',
            'method' => 'POST',
            'path' => '/vaults/{vault}/groups/{group}/members',
            'examplePath' => '/vaults/'.$vaultSlug.'/groups/'.$groupId.'/members',
            'description' => 'Put a contact of the vault in a group, optionally with a role and the dates they joined and left.',
            'body' => [
                'Somebody who left and came back gets a new membership, so the group keeps both periods. Somebody already in the group today cannot be added twice: that answers 422 on contact, saying they are already in this group.',
                'A left_on before joined_on answers 422 on left_on, saying somebody cannot leave a group before they joined it. A date sent without its precision is taken as known to the day.',
                $flagNote,
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $groupIdentifier,
            'bodyParams' => [
                ['name' => 'contact', 'type' => 'string', 'required' => true, 'description' => 'The person to add, a contact of the same vault, named by their slug or their id. A contact of another vault answers 404.', 'example' => $contactSlug],
                ...$membershipParams,
            ],
            'returns' => 'The membership object that was recorded.',
            'responseStatus' => 201,
            'response' => ['data' => $membership],
        ],
        [
            'id' => 'group-members-update',
            'flag' => 'groups',
            'returnFields' => ApiDocsFields::one('group_membership', 'membership', $membershipAttributes, withLinks: false),
            'title' => 'Change a membership',
            'label' => 'Change a membership',
            'method' => 'PUT',
            'path' => '/vaults/{vault}/groups/{group}/members/{membership}',
            'examplePath' => '/vaults/'.$vaultSlug.'/groups/'.$groupId.'/members/'.$membershipId,
            'description' => 'Change the role or the dates of one membership. Giving a left_on is how somebody leaves a group.',
            'body' => [
                'Every field is optional. A field you leave out keeps its value, and a role or a date sent as null is cleared. A precision left out keeps the one the date had, or day when the date had none.',
                'A left_on before joined_on answers 422 on left_on, saying somebody cannot leave a group before they joined it. Clearing left_on while the same person already holds another membership of the group today answers 422 on left_on, saying they are already in this group.',
                $flagNote,
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $membershipIdentifier,
            'bodyParams' => $membershipParams,
            'returns' => 'The membership object as it stands now.',
            'response' => ['data' => $membership],
        ],
        [
            'id' => 'group-members-destroy',
            'flag' => 'groups',
            'title' => 'Delete a membership',
            'label' => 'Delete a membership',
            'method' => 'DELETE',
            'path' => '/vaults/{vault}/groups/{group}/members/{membership}',
            'examplePath' => '/vaults/'.$vaultSlug.'/groups/'.$groupId.'/members/'.$membershipId,
            'description' => 'Delete a membership that was recorded by mistake.',
            'body' => [
                'Somebody who really left keeps their membership with a left_on instead, given by changing the membership. The contact is not deleted.',
                $flagNote,
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $membershipIdentifier,
            'returns' => 'Nothing at all.',
            'responseStatus' => 204,
        ],
        [
            'id' => 'contact-groups-index',
            'flag' => 'groups',
            'returnFields' => $contactGroupsFields,
            'title' => 'List the groups of a contact',
            'label' => 'List a contact\'s groups',
            'method' => 'GET',
            'path' => '/vaults/{vault}/contacts/{contact}/groups',
            'examplePath' => '/vaults/'.$vaultSlug.'/contacts/'.$contactSlug.'/groups',
            'description' => 'List the memberships of one contact: the groups they are in today first, then those they left, the most recent departure first.',
            'body' => [
                'The list is answered whole rather than a page at a time. Memberships of deleted groups are left out.',
                $flagNote,
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => [
                ...$vaultIdentifier,
                ['name' => 'contact', 'type' => 'string', 'required' => true, 'description' => 'The contact, named either by their slug or by their id. A contact addressed through the wrong vault answers 404.'],
            ],
            'returns' => 'Every membership object of the contact.',
            'response' => [
                'data' => [$membership],
            ],
        ],
    ],
];
