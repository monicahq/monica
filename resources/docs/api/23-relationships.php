<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();

$vaultSlug = 'dunder-mifflin';
$contactSlug = 'jim-halpert';
$relationshipId = '7c1e9a42-5b3d-4f60-a8e2-0d94b6c3f185';
$typeId = 'b52f0d7e-1c84-4a39-9e6b-3f7a20c5d816';

$relationship = [
    'type' => 'relationship',
    'id' => $relationshipId,
    'attributes' => [
        'relationship_type' => [
            'id' => $typeId,
            'name' => 'Partner',
            'is_symmetric' => true,
        ],
        'contact_a' => [
            'id' => '2d6b8f13-9a07-4c5e-b1f4-6e3a90d2c748',
            'slug' => 'jim-halpert',
            'name' => 'Jim Halpert',
            'is_partial' => false,
            'role' => 'partner',
        ],
        'contact_b' => [
            'id' => 'e8a41c06-7f25-4b93-8d0e-52c9b7f1a364',
            'slug' => 'pam-beesly',
            'name' => 'Pam Beesly',
            'is_partial' => false,
            'role' => 'partner',
        ],
        'status' => 'current',
        'started_on' => '2007-05-10',
        'ended_on' => null,
        'created_at' => 1790380800,
        'updated_at' => 1790380800,
    ],
    'links' => [
        'self' => $base.'/vaults/'.$vaultSlug.'/contacts/'.$contactSlug.'/relationships/'.$relationshipId,
    ],
];

$identifier = [
    ['name' => 'vault', 'type' => 'string', 'required' => true, 'description' => 'The vault both people live in, named either by the readable name it reports as slug or by its id.'],
    ['name' => 'contact', 'type' => 'string', 'required' => true, 'description' => 'One of the two people, named either by their slug or by their id. A contact addressed through the wrong vault answers 404.'],
];

$relationshipIdentifier = [
    ...$identifier,
    ['name' => 'relationship', 'type' => 'uuid', 'required' => true, 'description' => 'The id of the relationship. It is reached through either of its two people, and through anybody else answers 404.'],
];

$pageParams = [
    ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Which page to answer with.', 'default' => '1', 'example' => 2],
    ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'How many records one page holds, between 1 and 100. A number outside the range is brought back into it rather than refused.', 'default' => '25', 'example' => 10],
];

$person = fn (string $side, string $role): array => [
    ['name' => $side, 'type' => 'object', 'description' => "The person holding {$role}."],
    ['name' => "{$side}.id", 'type' => 'uuid', 'description' => 'The id of the contact.'],
    ['name' => "{$side}.slug", 'type' => 'string', 'description' => 'The readable name of the contact, which addresses it as well as its id.'],
    ['name' => "{$side}.name", 'type' => 'string', 'description' => 'The name of the contact, as it is shown.'],
    ['name' => "{$side}.is_partial", 'type' => 'boolean', 'description' => 'True for somebody who exists only through a relationship and has no record of their own.'],
    ['name' => "{$side}.role", 'type' => 'string', 'description' => "The role this person holds, which is the type's {$role}."],
];

$relationshipAttributes = [
    ['name' => 'relationship_type', 'type' => 'object', 'description' => 'What the link is.'],
    ['name' => 'relationship_type.id', 'type' => 'uuid', 'description' => 'The id of the relationship type.'],
    ['name' => 'relationship_type.name', 'type' => 'string', 'description' => 'The name of the relationship type.'],
    ['name' => 'relationship_type.is_symmetric', 'type' => 'boolean', 'description' => 'True when both people hold the same role, as partners do.'],
    ...$person('contact_a', 'role_a'),
    ...$person('contact_b', 'role_b'),
    ['name' => 'status', 'type' => 'string', 'description' => 'current or ended.'],
    ['name' => 'started_on', 'type' => 'string', 'description' => 'When it started, written as a year, a month and a day, or null.'],
    ['name' => 'ended_on', 'type' => 'string', 'description' => 'When it ended, or null. Always null while the relationship is current.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the relationship was recorded, as a count of seconds since 1970.'],
    ['name' => 'updated_at', 'type' => 'timestamp', 'description' => 'When it last changed, as a count of seconds. Equal to created_at until it first changes.'],
];

$typeParam = ['name' => 'relationship_type', 'type' => 'uuid', 'description' => 'The id of a relationship type of your account. A type that is switched off answers 422.', 'example' => $typeId];

$holdsRoleA = ['name' => 'holds_role_a', 'type' => 'boolean', 'description' => 'Whether the contact in the address holds role_a and the other person role_b. For a parent and child type, true says the contact is the parent. Ignored for a symmetric type.', 'example' => 'true'];

$namingParams = [
    ['name' => 'first_name', 'type' => 'string', 'required' => false, 'description' => 'The first name of somebody who has no record of their own. Up to 255 characters.', 'example' => 'Cece'],
    ['name' => 'last_name', 'type' => 'string', 'required' => false, 'description' => 'Their last name. Needs first_name.', 'example' => 'Halpert'],
    ['name' => 'creates_record', 'type' => 'boolean', 'required' => false, 'description' => 'True to give that person a full record of their own in the vault.', 'example' => 'false'],
];

return [
    'name' => 'Relationships',
    'description' => 'How the people of a vault are linked to each other. One relationship holds both sides, so it reads the same from either person.',
    'sections' => [
        [
            'id' => 'relationships-index',
            'returnFields' => ApiDocsFields::page('relationship', 'relationship', 'relationships', $relationshipAttributes),
            'title' => 'List a contact\'s relationships',
            'label' => 'List relationships',
            'method' => 'GET',
            'path' => '/vaults/{vault}/contacts/{contact}/relationships',
            'examplePath' => '/vaults/'.$vaultSlug.'/contacts/'.$contactSlug.'/relationships',
            'description' => 'List every relationship one contact is part of, whichever side they are on.',
            'body' => [
                'Read contact_a and contact_b to tell which side the contact is on. The other one is the person they are linked to.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $identifier,
            'queryParams' => $pageParams,
            'returns' => 'A page of relationship objects, and how many there are in all.',
            'response' => [
                'data' => [$relationship],
                'meta' => ['page' => 1, 'per_page' => 25, 'total' => 1],
            ],
        ],
        [
            'id' => 'relationships-create',
            'returnFields' => ApiDocsFields::one('relationship', 'relationship', $relationshipAttributes),
            'title' => 'Add a relationship',
            'label' => 'Add a relationship',
            'method' => 'POST',
            'path' => '/vaults/{vault}/contacts/{contact}/relationships',
            'examplePath' => '/vaults/'.$vaultSlug.'/contacts/'.$contactSlug.'/relationships',
            'description' => 'Link a contact to somebody else in the same vault.',
            'body' => [
                'The other person is an existing contact named by other_contact, somebody new named by first_name, or, when you send neither, somebody whose name is unknown. Somebody new has no record of their own unless creates_record is true.',
                'Linking somebody to themselves, using a type that is switched off, or making somebody their own ancestor answers 422, naming other_contact or relationship_type.',
                'The same two people can hold only one current relationship of a given type. Linking them again with that type answers 422 on other_contact, whichever of the two you start from when the type is symmetric. A relationship that ended does not count, so two people can be linked again after a break.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $identifier,
            'bodyParams' => [
                [...$typeParam, 'required' => true],
                ['name' => 'other_contact', 'type' => 'string', 'required' => false, 'description' => 'The other person, a contact of the same vault, named by their slug or their id. Not allowed with first_name or last_name.', 'example' => 'pam-beesly'],
                ...$namingParams,
                [...$holdsRoleA, 'required' => false, 'description' => $holdsRoleA['description'].' True when left out.'],
            ],
            'returns' => 'The relationship object that was added.',
            'responseStatus' => 201,
            'response' => ['data' => $relationship],
        ],
        [
            'id' => 'relationships-show',
            'returnFields' => ApiDocsFields::one('relationship', 'relationship', $relationshipAttributes),
            'title' => 'Get a relationship',
            'label' => 'Get a relationship',
            'method' => 'GET',
            'path' => '/vaults/{vault}/contacts/{contact}/relationships/{relationship}',
            'examplePath' => '/vaults/'.$vaultSlug.'/contacts/'.$contactSlug.'/relationships/'.$relationshipId,
            'description' => 'Retrieve one relationship through either of its two people.',
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $relationshipIdentifier,
            'returns' => 'A relationship object.',
            'response' => ['data' => $relationship],
        ],
        [
            'id' => 'relationships-update',
            'returnFields' => ApiDocsFields::one('relationship', 'relationship', $relationshipAttributes),
            'title' => 'Change a relationship',
            'label' => 'Change a relationship',
            'method' => 'PUT',
            'path' => '/vaults/{vault}/contacts/{contact}/relationships/{relationship}',
            'examplePath' => '/vaults/'.$vaultSlug.'/contacts/'.$contactSlug.'/relationships/'.$relationshipId,
            'description' => 'Change the type of a relationship, whether it ended, its dates or which side holds which role.',
            'body' => [
                'Every field is optional. A field you leave out keeps its value, and a date sent as null is cleared. Setting status back to current clears the end date.',
                'Changing the type, the roles or the status so that the two people would hold two current relationships of the same type answers 422 on relationship_type. A change that touches none of those three, such as a date, is always accepted.',
                'An end date before the start date answers 422 on ended_on. first_name, last_name and creates_record only apply to somebody without a record of their own, and answer 422 for anybody else, who is renamed on their own record.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $relationshipIdentifier,
            'bodyParams' => [
                [...$typeParam, 'required' => false],
                ['name' => 'status', 'type' => 'string', 'required' => false, 'description' => 'current or ended.', 'example' => 'ended'],
                ['name' => 'started_on', 'type' => 'string', 'required' => false, 'description' => 'When it started, written as a year, a month and a day.', 'example' => '2007-05-10'],
                ['name' => 'ended_on', 'type' => 'string', 'required' => false, 'description' => 'When it ended, for an ended relationship.', 'example' => '2008-01-15'],
                [...$holdsRoleA, 'required' => false, 'description' => $holdsRoleA['description'].' The current way round when left out.'],
                ...$namingParams,
            ],
            'returns' => 'The relationship object as it stands now.',
            'response' => ['data' => $relationship],
        ],
        [
            'id' => 'relationships-destroy',
            'title' => 'Delete a relationship',
            'label' => 'Delete a relationship',
            'method' => 'DELETE',
            'path' => '/vaults/{vault}/contacts/{contact}/relationships/{relationship}',
            'examplePath' => '/vaults/'.$vaultSlug.'/contacts/'.$contactSlug.'/relationships/'.$relationshipId,
            'description' => 'Delete a relationship. It leaves both people at once.',
            'body' => [
                'Neither person is deleted, including somebody who existed only through this relationship.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $relationshipIdentifier,
            'returns' => 'Nothing at all.',
            'responseStatus' => 204,
        ],
    ],
];
