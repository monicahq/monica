<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();
$application = rtrim((string) config('app.url'), '/');

$webLink = 'The page of the vault in the application, which opens for somebody signed in rather than for a key. It is the link to give a person.';

$vault = [
    'type' => 'vault',
    'id' => '9d4f1c2e-7b3a-4f88-9a2d-6c1e5b0f7a31',
    'attributes' => [
        'slug' => 'dunder-mifflin',
        'name' => 'Dunder Mifflin',
        'description' => 'Everybody at the Scranton branch.',
        'colour' => '#4f8a49',
        'position' => 1,
        'is_default' => true,
        'contact_count' => 12,
        'shows_people' => true,
        'shows_things' => true,
        'shows_knowledge' => false,
        'created_at' => 1756252800,
        'updated_at' => 1756339200,
    ],
    'links' => [
        'self' => $base.'/vaults/dunder-mifflin',
        'web' => $application.'/vaults/dunder-mifflin',
    ],
];

$otherVault = [
    'type' => 'vault',
    'id' => 'b17c6d40-2e59-4a1b-8f03-5d84e2a9c6f7',
    'attributes' => [
        'slug' => 'schrute-farms',
        'name' => 'Schrute Farms',
        'description' => null,
        'colour' => '#9a6a24',
        'position' => 2,
        'is_default' => false,
        'contact_count' => 3,
        'shows_people' => true,
        'shows_things' => true,
        'shows_knowledge' => true,
        'created_at' => 1756166400,
        'updated_at' => 1756166400,
    ],
    'links' => [
        'self' => $base.'/vaults/schrute-farms',
        'web' => $application.'/vaults/schrute-farms',
    ],
];

$pageParams = [
    [
        'name' => 'page',
        'type' => 'integer',
        'required' => false,
        'description' => 'Which page to answer with.',
        'default' => '1',
        'example' => 2,
    ],
    [
        'name' => 'per_page',
        'type' => 'integer',
        'required' => false,
        'description' => 'How many vaults one page holds, between 1 and 100. A number outside the range is brought back into it rather than refused.',
        'default' => '25',
        'example' => 10,
    ],
];

$identifier = [
    [
        'name' => 'vault',
        'type' => 'string',
        'required' => true,
        'description' => 'The vault, named either by the readable name it reports as slug or by its id. Both answer, whichever the account builds its own addresses from. A readable name is rebuilt whenever the record is renamed, and the previous one stops answering, so a client that stores a reference stores the id. A name a record has released is never given to another.',
    ],
];

$colours = 'Exactly one of #3f5fd0, #4f8a49, #9a6a24, #9a3f2b, #6b4fa8, #2f7f86, #a8447a or #4a4a52, written in lower case.';

$vaultAttributes = [
    ['name' => 'slug', 'type' => 'string', 'description' => 'The readable name the vault is addressed by, built from its name and unique within your account. It is not stable: renaming the vault rebuilds it and the previous one stops answering, which is why a stored reference should be the id. Nothing lets you choose or edit it.'],
    ['name' => 'name', 'type' => 'string', 'description' => 'What the vault is called. Up to 255 characters. Reduced to plain text when it was written down, so no markup survives here.'],
    ['name' => 'description', 'type' => 'string', 'description' => 'What the vault is for, up to 65535 characters, or null when nobody said. Null is the ordinary state of a vault nobody described, not a sign of anything missing.'],
    ['name' => 'colour', 'type' => 'string', 'description' => $colours.' It is how vaults are told apart at a glance, and it is decoration: nothing depends on it.'],
    ['name' => 'position', 'type' => 'integer', 'description' => 'Where the vault sits in your list, counting from 1. It is the order you arranged, and it is the order the list endpoint answers in.'],
    ['name' => 'is_default', 'type' => 'boolean', 'description' => 'Whether records land here when nothing says which vault. Exactly one of your vaults is true while you hold a vault at all, and none is while you hold none.'],
    ['name' => 'contact_count', 'type' => 'integer', 'description' => 'How many contacts the vault holds, counting the archived and the partial ones. It is a running count kept as contacts are written down and deleted, rather than something counted when you ask.'],
    ['name' => 'shows_people', 'type' => 'boolean', 'description' => 'Whether the screens of the vault show People: its contacts, entities and groups, in the sidebar and in search. True for every new vault. It changes only what the application draws: every endpoint here answers the same either way.'],
    ['name' => 'shows_things', 'type' => 'boolean', 'description' => 'Whether the sidebar of the vault shows Things, its possessions. True for every new vault. It changes only what the application draws.'],
    ['name' => 'shows_knowledge', 'type' => 'boolean', 'description' => 'Whether the sidebar of the vault shows Knowledge, its notes and lists. True for every new vault. It changes only what the application draws.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the vault was opened, as a count of seconds since 1970 rather than a written date.'],
    ['name' => 'updated_at', 'type' => 'timestamp', 'description' => 'When its wording last changed, as a count of seconds. Equal to created_at until it first changes.'],
];

return [
    'name' => 'Vaults',
    'description' => 'The compartments an account is divided into. A record belongs to exactly one, and moving it between them is deliberate.',
    'sections' => [
        [
            'id' => 'vaults-index',
            'returnFields' => ApiDocsFields::page('vault', 'vault', 'vaults', $vaultAttributes, webLink: $webLink),
            'title' => 'List vaults',
            'label' => 'List vaults',
            'method' => 'GET',
            'path' => '/vaults',
            'description' => 'List your vaults, in the order you arranged them.',
            'body' => [
                'The order is the one you gave your vaults, which is the order they appear in when you switch between them. It is not the order they were made in, so a vault you moved to the top comes back at the top.',
                'A deleted vault is never listed. An account holding no vault answers with an empty list rather than an error, since that is the state every account starts in.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'queryParams' => $pageParams,
            'returns' => 'A page of vault objects, and how many there are in all.',
            'response' => [
                'data' => [$vault, $otherVault],
                'meta' => [
                    'page' => 1,
                    'per_page' => 25,
                    'total' => 2,
                ],
            ],
        ],
        [
            'id' => 'vaults-show',
            'returnFields' => ApiDocsFields::one('vault', 'vault', $vaultAttributes, webLink: $webLink),
            'title' => 'Get a vault',
            'label' => 'Get a vault',
            'method' => 'GET',
            'path' => '/vaults/{vault}',
            'examplePath' => '/vaults/9d4f1c2e-7b3a-4f88-9a2d-6c1e5b0f7a31',
            'description' => 'Retrieve one of your vaults: what it is called, what it is for, and how many contacts it holds.',
            'body' => [
                'A vault is a watertight compartment. Contacts, records of the kinds you invented, files and private links belong to exactly one, and nothing reaches across.',
                'What a new contact in the vault starts from is not in here. A vault holds one of those per kind of record, so a single field would answer a narrower question than its name suggests. You can set the contact one when you create or update a vault, and reading them back is an endpoint of its own.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $identifier,
            'returns' => 'A vault object. A vault belonging to somebody else answers 404, which is also what a vault that never existed answers, and what a deleted one answers.',
            'response' => ['data' => $vault],
        ],
        [
            'id' => 'vaults-create',
            'returnFields' => ApiDocsFields::one('vault', 'vault', $vaultAttributes, webLink: $webLink),
            'title' => 'Create a vault',
            'label' => 'Create a vault',
            'method' => 'POST',
            'path' => '/vaults',
            'description' => 'Open a compartment. It starts empty, and it goes at the end of your list.',
            'body' => [
                'If your account held no vault, this one becomes the vault records land in when nothing says which. Exactly one vault is that at any time, and there was nothing else to be it.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'bodyParams' => [
                [
                    'name' => 'name',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'What the vault is called. Up to 255 characters. Markup and surrounding blank space are stripped.',
                    'example' => 'Dunder Mifflin',
                ],
                [
                    'name' => 'description',
                    'type' => 'string',
                    'required' => false,
                    'description' => 'What the vault is for. Up to 65535 characters. Absent, empty or null all mean it has none.',
                    'example' => 'Everybody at the Scranton branch.',
                ],
                [
                    'name' => 'colour',
                    'type' => 'string',
                    'required' => false,
                    'description' => $colours.' Absent means #3f5fd0.',
                    'default' => '#3f5fd0',
                    'example' => '#4f8a49',
                ],
                [
                    'name' => 'is_default',
                    'type' => 'boolean',
                    'required' => false,
                    'description' => 'True makes this the vault records land in when nothing says which, and takes that over from whichever vault held it. False, or saying nothing, changes nothing: exactly one of your vaults is it while you have a vault at all, so there would be nothing to hand it to.',
                ],
                [
                    'name' => 'structure_version_id',
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'The published version of one of your active contact templates. Every new contact in this vault starts from it, beginning with the contact standing for you, which is created with the vault. Left out, the vault starts from Basic contact, or from the next template Monica supplied when Basic contact is gone. A draft, a version belonging to somebody else, and a version describing something other than a contact are each refused.',
                ],
            ],
            'returns' => 'The new vault object. Keep its id: every other call here names the vault by it.',
            'responseStatus' => 201,
            'response' => ['data' => $vault],
        ],
        [
            'id' => 'vaults-update',
            'returnFields' => ApiDocsFields::one('vault', 'vault', $vaultAttributes, webLink: $webLink),
            'title' => 'Update a vault',
            'label' => 'Update a vault',
            'method' => 'PUT',
            'path' => '/vaults/{vault}',
            'examplePath' => '/vaults/9d4f1c2e-7b3a-4f88-9a2d-6c1e5b0f7a31',
            'description' => 'Change what a vault is called, what it is for, the colour it is told apart by, and which sections its screens show.',
            'body' => [
                'Send only what changes. A name or a colour sent as null is refused with 422, and a description sent as null is cleared.',
                'What the vault holds is untouched, and so are its place in your list and whether it is the vault records land in by default.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $identifier,
            'bodyParams' => [
                [
                    'name' => 'name',
                    'type' => 'string',
                    'required' => false,
                    'description' => 'What the vault is called. Up to 255 characters. It cannot be null.',
                    'example' => 'Scranton',
                ],
                [
                    'name' => 'description',
                    'type' => 'string or null',
                    'required' => false,
                    'description' => 'What the vault is for. Up to 65535 characters. Null clears it.',
                    'example' => 'The branch, and everybody who walks through it.',
                ],
                [
                    'name' => 'colour',
                    'type' => 'string',
                    'required' => false,
                    'description' => $colours,
                    'example' => '#9a3f2b',
                ],
                [
                    'name' => 'is_default',
                    'type' => 'boolean',
                    'required' => false,
                    'description' => 'True makes this the vault records land in when nothing says which, and takes that over from whichever vault held it. False, or saying nothing, changes nothing: exactly one of your vaults is it while you have a vault at all, so there would be nothing to hand it to.',
                ],
                [
                    'name' => 'structure_version_id',
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'The published version of one of your active contact templates. A new contact in this vault starts from it. Contacts already created keep the version they were created on. A draft, a version belonging to somebody else, and a version describing something other than a contact are each refused.',
                ],
                [
                    'name' => 'shows_people',
                    'type' => 'boolean',
                    'required' => false,
                    'description' => 'False hides People (contacts, entities and groups) from the sidebar and the search of the vault in the application. Nothing is deleted, and no endpoint answers differently.',
                    'example' => false,
                ],
                [
                    'name' => 'shows_things',
                    'type' => 'boolean',
                    'required' => false,
                    'description' => 'False hides Things, the possessions, from the sidebar of the vault in the application. Nothing is deleted.',
                ],
                [
                    'name' => 'shows_knowledge',
                    'type' => 'boolean',
                    'required' => false,
                    'description' => 'False hides Knowledge, the notes and lists, from the sidebar of the vault in the application. Nothing is deleted.',
                ],
            ],
            'returns' => 'The vault object as it now reads. A vault belonging to somebody else answers 404 and is left as it was.',
            'response' => ['data' => $vault],
        ],
        [
            'id' => 'vaults-destroy',
            'title' => 'Delete a vault',
            'label' => 'Delete a vault',
            'method' => 'DELETE',
            'path' => '/vaults/{vault}',
            'examplePath' => '/vaults/9d4f1c2e-7b3a-4f88-9a2d-6c1e5b0f7a31',
            'description' => 'Delete a vault and everything it separates from the rest.',
            'body' => [
                'This is the most destructive call here, short of deleting your account, and there is no second step: making the call is the confirmation. Read what follows before you make it.',
                'The contacts the vault holds go with it, and so do the records of the kinds you invented. Every private link onto the vault, or onto the contacts in it, stops opening. That revocation is permanent, and bringing the vault back would not reopen them: you would decide again who may read it.',
                'If this was the vault records land in by default, the first vault left takes that over. If no vault is left, your account has no default and holds nothing, which is the state every account starts in. The last vault of an account may be deleted.',
                'The deletion is soft, so nothing is destroyed, but no endpoint here brings a vault back today. Treat it as final.',
                'While you have turned off the list of vaults (shows_vault_list is false on GET /me), your default vault cannot be deleted. The call answers 422 on vault with: You cannot delete your default vault while the list of vaults is turned off. Turn the list back on in Preferences first. Nothing is deleted. Your other vaults can still be deleted.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $identifier,
            'returns' => 'Nothing at all. A vault belonging to somebody else answers 404, and is not deleted. So does a vault you have already deleted, so calling this twice does not report a second deletion.',
            'responseStatus' => 204,
        ],
    ],
];
